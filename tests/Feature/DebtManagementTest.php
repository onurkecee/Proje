<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCardStatement;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_credit_card_statement_payments_are_transfers_and_reduce_card_debt_without_duplicate_expenses(): void
    {
        Carbon::setTestNow('2026-10-25 12:00:00');
        CarbonImmutable::setTestNow('2026-10-25 12:00:00');
        $user = User::factory()->create();
        $bank = Account::factory()->for($user)->create([
            'name' => 'Banka',
            'opening_balance_minor' => 20_000,
        ]);
        $card = Account::factory()->for($user)->create([
            'name' => 'Kredi kartım',
            'type' => 'credit_card',
            'opening_balance_minor' => 0,
        ]);
        $category = Category::factory()->for($user)->create([
            'name' => 'Alışveriş',
            'type' => 'expense',
        ]);
        $purchase = Transaction::factory()->for($user)->create([
            'account_id' => $card->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount_minor' => 10_000,
        ]);

        $this->actingAs($user)
            ->post(route('credit-card-statements.store'), [
                'account_id' => $card->id,
                'period_start' => '2026-09-25',
                'period_end' => '2026-10-24',
                'due_on' => '2026-11-05',
                'amount' => '80,00',
            ])
            ->assertRedirect(route('credit-card-statements.index'));

        $statement = CreditCardStatement::query()->firstOrFail();

        $this->post(route('credit-card-statements.store'), [
            'account_id' => $card->id,
            'period_start' => '2026-09-25',
            'period_end' => '2026-10-24',
            'due_on' => '2026-11-05',
            'amount' => '80,00',
        ])->assertSessionHasErrors('period_start');

        $this->post(route('credit-card-statements.pay.store', $statement->id), [
            'account_id' => $bank->id,
            'amount' => '30,00',
            'paid_at' => '2026-10-25',
        ])->assertRedirect(route('credit-card-statements.index'));

        $statement->refresh();
        $this->assertSame(3_000, $statement->paid_amount_minor);
        $this->assertNull($statement->paid_at);
        $this->assertSame(2, Transaction::query()->count());
        $this->assertDatabaseHas('transactions', [
            'credit_card_statement_id' => $statement->id,
            'type' => 'transfer',
            'account_id' => $bank->id,
            'destination_account_id' => $card->id,
            'category_id' => null,
            'amount_minor' => 3_000,
        ]);

        $this->post(route('credit-card-statements.pay.store', $statement->id), [
            'account_id' => $bank->id,
            'amount' => '51,00',
            'paid_at' => '2026-10-25',
        ])->assertSessionHasErrors('amount');

        $this->post(route('credit-card-statements.pay.store', $statement->id), [
            'account_id' => $bank->id,
            'amount' => '50,00',
            'paid_at' => '2026-10-25',
        ])->assertRedirect(route('credit-card-statements.index'));

        $statement->refresh();
        $this->assertSame(8_000, $statement->paid_amount_minor);
        $this->assertNotNull($statement->paid_at);
        $this->assertSame(3, Transaction::query()->count());
        $this->assertSame(1, Transaction::query()->where('type', 'expense')->count());
        $this->assertSame(2, Transaction::query()->where('type', 'transfer')->count());
        $this->get(route('credit-card-statements.index'))->assertOk();

        $this->get(route('transactions.edit', $purchase->id))->assertOk();
        $payment = $statement->transactions()->firstOrFail();
        $this->get(route('transactions.edit', $payment->id))->assertForbidden();
        $this->delete(route('transactions.destroy', $payment->id))->assertForbidden();
        $this->put(route('credit-card-statements.update', $statement->id), [
            'account_id' => $card->id,
            'period_start' => '2026-09-25',
            'period_end' => '2026-10-24',
            'due_on' => '2026-11-05',
            'amount' => '80,00',
        ])->assertForbidden();

        $accountsPage = $this->get(route('accounts.index'))->assertOk();
        $payload = html_entity_decode($accountsPage->getContent(), ENT_QUOTES);
        $this->assertStringContainsString('"balanceMinor":"12000"', $payload);
        $this->assertStringContainsString('"balanceMinor":"-2000"', $payload);
    }

    public function test_card_statement_isolation_currency_and_account_type_are_enforced(): void
    {
        Carbon::setTestNow('2026-10-25 12:00:00');
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $card = Account::factory()->for($user)->create(['type' => 'credit_card']);
        $otherCard = Account::factory()->for($otherUser)->create(['type' => 'credit_card']);
        $foreignBank = Account::factory()->for($otherUser)->create();
        $foreignStatement = $otherUser->creditCardStatements()->create([
            'account_id' => $otherCard->id,
            'period_start' => '2026-09-25',
            'period_end' => '2026-10-24',
            'due_on' => '2026-11-05',
            'amount_minor' => 5_000,
            'paid_amount_minor' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('credit-card-statements.pay.create', $foreignStatement->id))
            ->assertNotFound();

        $this->from(route('credit-card-statements.create'))
            ->post(route('credit-card-statements.store'), [
                'account_id' => $foreignBank->id,
                'period_start' => '2026-09-25',
                'period_end' => '2026-10-24',
                'due_on' => '2026-11-05',
                'amount' => '50,00',
            ])
            ->assertSessionHasErrors('account_id');

        $dollarCard = Account::factory()->for($user)->create([
            'type' => 'credit_card',
            'currency' => 'USD',
        ]);
        $dollarStatement = $user->creditCardStatements()->create([
            'account_id' => $dollarCard->id,
            'period_start' => '2026-09-25',
            'period_end' => '2026-10-24',
            'due_on' => '2026-11-05',
            'amount_minor' => 5_000,
            'paid_amount_minor' => 0,
        ]);
        $this->post(route('credit-card-statements.pay.store', $dollarStatement->id), [
            'account_id' => Account::factory()->for($user)->create()->id,
            'amount' => '50,00',
            'paid_at' => '2026-10-25',
        ])->assertSessionHasErrors('account_id');

        $account = Account::factory()->for($user)->create();
        $expenseCategory = Category::factory()->for($user)->create(['type' => 'expense']);
        Transaction::factory()->for($user)->create([
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'type' => 'expense',
        ]);

        $this->from(route('accounts.edit', $account->id))
            ->put(route('accounts.update', $account->id), [
                'name' => $account->name,
                'type' => 'credit_card',
                'opening_balance' => '0,00',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('type');

        $this->assertSame('bank', $account->fresh()->type->value);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame('credit_card', $card->fresh()->type->value);
    }

    public function test_loan_schedule_clamps_month_end_and_last_installment_matches_total(): void
    {
        Carbon::setTestNow('2026-10-25 12:00:00');
        CarbonImmutable::setTestNow('2026-10-25 12:00:00');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('loans.store'), [
                'name' => 'Taşıt kredisi',
                'lender' => 'Banka',
                'total_repayment' => '100,00',
                'installment_amount' => '30,00',
                'installment_count' => '4',
                'first_due_on' => '2026-10-31',
            ])
            ->assertRedirect(route('loans.index'));

        $loan = Loan::query()->firstOrFail();
        $this->assertSame(100_00, $loan->total_repayment_minor);
        $this->assertSame([3_000, 3_000, 3_000, 1_000], $loan->installments()->orderBy('installment_number')->pluck('amount_minor')->all());
        $this->assertSame([
            '2026-10-31',
            '2026-11-30',
            '2026-12-31',
            '2027-01-31',
        ], $loan->installments()
            ->orderBy('installment_number')
            ->get(['due_on'])
            ->map(fn (LoanInstallment $installment): string => $installment->due_on->toDateString())
            ->all());

        $this->from(route('loans.create'))
            ->post(route('loans.store'), [
                'name' => 'Hatalı kredi',
                'total_repayment' => '100,00',
                'installment_amount' => '50,00',
                'installment_count' => '3',
                'first_due_on' => '2026-10-31',
            ])
            ->assertSessionHasErrors('installment_amount');
    }

    public function test_loan_installments_support_partial_expense_payments_and_protect_linked_transactions(): void
    {
        Carbon::setTestNow('2026-10-25 12:00:00');
        CarbonImmutable::setTestNow('2026-10-25 12:00:00');
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['opening_balance_minor' => 10_000]);
        $category = Category::factory()->for($user)->create(['type' => 'expense']);
        $loan = $user->loans()->create([
            'name' => 'İhtiyaç kredisi',
            'lender' => null,
            'total_repayment_minor' => 10_000,
            'installment_amount_minor' => 5_000,
            'installment_count' => 2,
            'first_due_on' => '2026-10-25',
            'is_active' => true,
        ]);
        $loan->installments()->create([
            'user_id' => $user->id,
            'installment_number' => 1,
            'due_on' => '2026-10-25',
            'amount_minor' => 5_000,
            'paid_amount_minor' => 0,
        ]);
        $installment = $loan->installments()->firstOrFail();

        $this->actingAs($user)
            ->post(route('loans.installments.pay.store', [
                'loan' => $loan->id,
                'installment' => $installment->id,
            ]), [
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => '20,00',
                'paid_at' => '2026-10-25',
            ])
            ->assertRedirect(route('loans.index'));

        $installment->refresh();
        $this->assertSame(2_000, $installment->paid_amount_minor);
        $this->assertNull($installment->paid_at);

        $this->post(route('loans.installments.pay.store', [
            'loan' => $loan->id,
            'installment' => $installment->id,
        ]), [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => '31,00',
            'paid_at' => '2026-10-25',
        ])->assertSessionHasErrors('amount');

        $this->post(route('loans.installments.pay.store', [
            'loan' => $loan->id,
            'installment' => $installment->id,
        ]), [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => '30,00',
            'paid_at' => '2026-10-25',
        ])->assertRedirect(route('loans.index'));

        $installment->refresh();
        $this->assertSame(5_000, $installment->paid_amount_minor);
        $this->assertNotNull($installment->paid_at);
        $this->assertSame(2, $installment->transactions()->count());
        $this->assertSame(2, Transaction::query()->where('type', 'expense')->count());
        $this->get(route('loans.index'))->assertOk();

        $payment = $installment->transactions()->firstOrFail();
        $this->get(route('transactions.edit', $payment->id))->assertForbidden();
        $this->delete(route('transactions.destroy', $payment->id))->assertForbidden();
        $this->get(route('loans.edit', $loan->id))->assertForbidden();
        $this->assertDatabaseHas('transactions', [
            'id' => $payment->id,
            'loan_installment_id' => $installment->id,
            'type' => 'expense',
            'category_id' => $category->id,
        ]);
    }

    public function test_loan_installments_and_payments_are_scoped_to_the_owner(): void
    {
        Carbon::setTestNow('2026-10-25 12:00:00');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $loan = $other->loans()->create([
            'name' => 'Diğer kullanıcının kredisi',
            'lender' => null,
            'total_repayment_minor' => 5_000,
            'installment_amount_minor' => 5_000,
            'installment_count' => 1,
            'first_due_on' => '2026-10-25',
            'is_active' => true,
        ]);
        $installment = $loan->installments()->create([
            'user_id' => $other->id,
            'installment_number' => 1,
            'due_on' => '2026-10-25',
            'amount_minor' => 5_000,
            'paid_amount_minor' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('loans.installments.pay.create', [
                'loan' => $loan->id,
                'installment' => $installment->id,
            ]))
            ->assertNotFound();

        $this->assertDatabaseCount('transactions', 0);
    }
}
