<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Category;
use App\Models\User;
use App\Services\BillOccurrenceGenerator;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_monthly_bill_occurrences_clamp_to_month_end_and_are_idempotent(): void
    {
        Carbon::setTestNow('2026-10-19 12:00:00');
        CarbonImmutable::setTestNow('2026-10-19 12:00:00');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('bills.store'), [
                'name' => 'Elektrik',
                'type' => 'electricity',
                'expected_amount' => '1.234,56',
                'first_due_on' => '2026-10-31',
                'repeat_monthly' => '1',
            ])
            ->assertSessionHasErrors('expected_amount');

        $this->post(route('bills.store'), [
            'name' => 'Elektrik',
            'type' => 'electricity',
            'expected_amount' => '1234,56',
            'first_due_on' => '2026-10-31',
            'repeat_monthly' => '1',
        ])->assertRedirect(route('bills.index'));

        $bill = Bill::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertSame(123_456, $bill->expected_amount_minor);
        $this->assertSame([
            '2026-10-31',
            '2026-11-30',
            '2026-12-31',
            '2027-01-31',
            '2027-02-28',
            '2027-03-31',
            '2027-04-30',
        ], $bill->occurrences()
            ->orderBy('due_date')
            ->get(['due_date'])
            ->map(fn ($occurrence): string => $occurrence->due_date->toDateString())
            ->all());
        $this->assertSame(0, app(BillOccurrenceGenerator::class)->generateFor($bill));

        Carbon::setTestNow('2027-04-19 12:00:00');
        CarbonImmutable::setTestNow('2027-04-19 12:00:00');
        $this->assertSame(6, app(BillOccurrenceGenerator::class)->generateFor($bill));
        $this->assertSame(13, $bill->occurrences()->count());
    }

    public function test_paying_a_bill_creates_one_expense_and_updates_its_occurrence(): void
    {
        Carbon::setTestNow('2026-10-19 12:00:00');
        CarbonImmutable::setTestNow('2026-10-19 12:00:00');
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['opening_balance_minor' => 20_000]);
        $category = Category::factory()->for($user)->create(['name' => 'Faturalar', 'type' => 'expense']);
        $bill = $user->bills()->create([
            'name' => 'İnternet',
            'type' => 'internet',
            'expected_amount_minor' => 10_000,
            'first_due_on' => '2026-10-19',
            'repeat_monthly' => false,
            'is_active' => true,
        ]);
        $occurrence = $bill->occurrences()->create([
            'user_id' => $user->id,
            'due_date' => '2026-10-19',
            'amount_minor' => 10_000,
        ]);

        $this->actingAs($user)
            ->post(route('bills.occurrences.store', [
                'bill' => $bill->id,
                'occurrence' => $occurrence->id,
            ]), [
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => '120,50',
                'paid_at' => '2026-10-19',
            ])
            ->assertRedirect(route('bills.index'));

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'bill_occurrence_id' => $occurrence->id,
            'type' => 'expense',
            'amount_minor' => 12_050,
            'occurred_at' => '2026-10-19 00:00:00',
        ]);
        $this->assertDatabaseHas('bill_occurrences', [
            'id' => $occurrence->id,
            'paid_amount_minor' => 12_050,
            'paid_at' => '2026-10-19 00:00:00',
        ]);

        $transaction = $bill->occurrences()->firstOrFail()->transaction()->firstOrFail();
        $this->get(route('transactions.edit', $transaction->id))->assertForbidden();
        $this->delete(route('transactions.destroy', $transaction->id))->assertForbidden();
        $this->assertDatabaseCount('transactions', 1);

        $accountPage = $this->get(route('accounts.index'))->assertOk();
        $this->assertStringContainsString(
            '"balanceMinor":"7950"',
            html_entity_decode($accountPage->getContent(), ENT_QUOTES),
        );
        $transactionPage = $this->get(route('transactions.index'))->assertOk();
        $this->assertStringContainsString(
            '"isBillPayment":true',
            html_entity_decode($transactionPage->getContent(), ENT_QUOTES),
        );
        $billPage = $this->get(route('bills.index'))->assertOk();
        $billPagePayload = html_entity_decode($billPage->getContent(), ENT_QUOTES);
        $this->assertStringContainsString('"upcoming":[]', $billPagePayload);
        $this->assertStringContainsString('"amountMinor":"12050","currency":"TRY"', $billPagePayload);

        $this->post(route('bills.occurrences.store', [
            'bill' => $bill->id,
            'occurrence' => $occurrence->id,
        ]), [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => '120,50',
            'paid_at' => '2026-10-19',
        ])->assertNotFound();

        $user->delete();
        $this->assertDatabaseMissing('bills', ['id' => $bill->id]);
        $this->assertDatabaseMissing('bill_occurrences', ['id' => $occurrence->id]);
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_bill_payment_rejects_foreign_and_non_try_accounts(): void
    {
        Carbon::setTestNow('2026-10-19 12:00:00');
        CarbonImmutable::setTestNow('2026-10-19 12:00:00');
        $user = User::factory()->create();
        $foreignUser = User::factory()->create();
        $foreignAccount = Account::factory()->for($foreignUser)->create();
        $dollarAccount = Account::factory()->for($user)->create(['currency' => 'USD']);
        $foreignCategory = Category::factory()->for($foreignUser)->create(['type' => 'expense']);
        $bill = $user->bills()->create([
            'name' => 'Su',
            'type' => 'water',
            'expected_amount_minor' => 5_000,
            'first_due_on' => '2026-10-19',
            'repeat_monthly' => false,
            'is_active' => true,
        ]);
        $occurrence = $bill->occurrences()->create([
            'user_id' => $user->id,
            'due_date' => '2026-10-19',
            'amount_minor' => 5_000,
        ]);

        $this->actingAs($user)
            ->from(route('bills.index'))
            ->post(route('bills.occurrences.store', [
                'bill' => $bill->id,
                'occurrence' => $occurrence->id,
            ]), [
                'account_id' => $foreignAccount->id,
                'category_id' => $foreignCategory->id,
                'amount' => '50,00',
                'paid_at' => '2026-10-19',
            ])
            ->assertSessionHasErrors(['account_id', 'category_id']);

        $this->post(route('bills.occurrences.store', [
            'bill' => $bill->id,
            'occurrence' => $occurrence->id,
        ]), [
            'account_id' => $dollarAccount->id,
            'category_id' => Category::factory()->for($user)->create(['type' => 'expense'])->id,
            'amount' => '50,00',
            'paid_at' => '2026-10-19',
        ])->assertSessionHasErrors('account_id');

        $foreignBill = $foreignUser->bills()->create([
            'name' => 'Telefon',
            'type' => 'phone',
            'expected_amount_minor' => 3_000,
            'first_due_on' => '2026-10-19',
            'repeat_monthly' => false,
            'is_active' => true,
        ]);
        $foreignOccurrence = $foreignBill->occurrences()->create([
            'user_id' => $foreignUser->id,
            'due_date' => '2026-10-19',
            'amount_minor' => 3_000,
        ]);

        $this->get(route('bills.occurrences.create', [
            'bill' => $foreignBill->id,
            'occurrence' => $foreignOccurrence->id,
        ]))->assertNotFound();

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_bill_dashboard_counts_overdue_and_upcoming_periods_separately(): void
    {
        Carbon::setTestNow('2026-10-19 12:00:00');
        CarbonImmutable::setTestNow('2026-10-19 12:00:00');
        $user = User::factory()->create();

        foreach ([
            ['name' => 'Gecikmiş su', 'due' => '2026-10-18', 'amount' => 3_000],
            ['name' => 'Yaklaşan elektrik', 'due' => '2026-10-19', 'amount' => 4_000],
        ] as $billData) {
            $bill = $user->bills()->create([
                'name' => $billData['name'],
                'type' => 'other',
                'expected_amount_minor' => $billData['amount'],
                'first_due_on' => $billData['due'],
                'repeat_monthly' => false,
                'is_active' => true,
            ]);
            $bill->occurrences()->create([
                'user_id' => $user->id,
                'due_date' => $billData['due'],
                'amount_minor' => $billData['amount'],
            ]);
        }

        $response = $this->actingAs($user)->get(route('bills.index'))->assertOk();
        $payload = html_entity_decode($response->getContent(), ENT_QUOTES);

        $this->assertStringContainsString('"overdueCount":1', $payload);
        $this->assertStringContainsString('"dueSoonCount":1', $payload);
        $this->assertStringContainsString('"dueSoonMinor":"4000"', $payload);
    }

    public function test_user_can_delete_an_unpaid_future_bill_but_not_a_paid_bill(): void
    {
        Carbon::setTestNow('2026-10-19 12:00:00');
        CarbonImmutable::setTestNow('2026-10-19 12:00:00');
        $user = User::factory()->create();
        $futureBill = $user->bills()->create([
            'name' => 'Tek seferlik abonelik',
            'type' => 'other',
            'expected_amount_minor' => 2_500,
            'first_due_on' => '2026-10-25',
            'repeat_monthly' => false,
            'is_active' => true,
        ]);
        $futureBill->occurrences()->create([
            'user_id' => $user->id,
            'due_date' => '2026-10-25',
            'amount_minor' => 2_500,
        ]);

        $this->actingAs($user)
            ->delete(route('bills.destroy', $futureBill->id))
            ->assertRedirect(route('bills.index'));
        $this->assertDatabaseMissing('bills', ['id' => $futureBill->id]);

        $paidBill = $user->bills()->create([
            'name' => 'Ödenmiş fatura',
            'type' => 'other',
            'expected_amount_minor' => 2_500,
            'first_due_on' => '2026-10-10',
            'repeat_monthly' => false,
            'is_active' => true,
        ]);
        $paidBill->occurrences()->create([
            'user_id' => $user->id,
            'due_date' => '2026-10-10',
            'amount_minor' => 2_500,
            'paid_at' => '2026-10-10',
            'paid_amount_minor' => 2_500,
        ]);

        $this->delete(route('bills.destroy', $paidBill->id))->assertForbidden();
        $this->assertDatabaseHas('bills', ['id' => $paidBill->id]);
    }
}
