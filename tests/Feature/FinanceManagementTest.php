<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_an_account_with_exact_minor_units(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => 'Vadesiz hesabım',
                'type' => 'bank',
                'opening_balance' => '1250,50',
            ])
            ->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => 'Vadesiz hesabım',
            'opening_balance_minor' => 125_050,
        ]);
    }

    public function test_transaction_amounts_and_transfer_balances_use_integer_minor_units(): void
    {
        $user = User::factory()->create();
        $source = Account::factory()->for($user)->create([
            'name' => 'Birinci hesap',
            'opening_balance_minor' => 10_000,
        ]);
        $destination = Account::factory()->for($user)->create([
            'name' => 'İkinci hesap',
            'opening_balance_minor' => 5_000,
        ]);
        $incomeCategory = Category::factory()->for($user)->create([
            'name' => 'Maaş',
            'type' => 'income',
        ]);
        $expenseCategory = Category::factory()->for($user)->create([
            'name' => 'Market',
            'type' => 'expense',
        ]);

        $this->actingAs($user);

        $this->post(route('transactions.store'), [
            'type' => 'income',
            'account_id' => $source->id,
            'category_id' => $incomeCategory->id,
            'amount' => '100,25',
            'occurred_at' => '2026-10-01',
        ])->assertRedirect(route('transactions.index'));

        $this->post(route('transactions.store'), [
            'type' => 'expense',
            'account_id' => $source->id,
            'category_id' => $expenseCategory->id,
            'amount' => '24.70',
            'occurred_at' => '2026-10-02',
        ])->assertRedirect(route('transactions.index'));

        $this->post(route('transactions.store'), [
            'type' => 'transfer',
            'account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'amount' => '10,00',
            'occurred_at' => '2026-10-03',
        ])->assertRedirect(route('transactions.index'));

        $this->assertDatabaseCount('transactions', 3);
        $this->assertDatabaseHas('transactions', [
            'type' => 'income',
            'amount_minor' => 10_025,
        ]);
        $this->assertDatabaseHas('transactions', [
            'type' => 'expense',
            'amount_minor' => 2_470,
        ]);

        $response = $this->get(route('accounts.index'));

        $response->assertOk();
        $pagePayload = html_entity_decode($response->getContent(), ENT_QUOTES);
        $this->assertStringContainsString('"balanceMinor":"16555"', $pagePayload);
        $this->assertStringContainsString('"balanceMinor":"6000"', $pagePayload);
    }

    public function test_transaction_validation_rejects_zero_amount_and_foreign_accounts(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create(['type' => 'expense']);
        $foreignAccount = Account::factory()->create();

        $this->actingAs($user)
            ->from(route('transactions.create'))
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => '0,00',
                'occurred_at' => '2026-10-04',
            ])
            ->assertSessionHasErrors('amount');

        $this->from(route('transactions.create'))
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'account_id' => $foreignAccount->id,
                'category_id' => $category->id,
                'amount' => '10,00',
                'occurred_at' => '2026-10-04',
            ])
            ->assertSessionHasErrors('account_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transfer_requires_a_different_same_currency_account(): void
    {
        $user = User::factory()->create();
        $source = Account::factory()->for($user)->create();
        $differentCurrency = Account::factory()->for($user)->create(['currency' => 'USD']);

        $this->actingAs($user)
            ->from(route('transactions.create'))
            ->post(route('transactions.store'), [
                'type' => 'transfer',
                'account_id' => $source->id,
                'destination_account_id' => $source->id,
                'amount' => '10,00',
                'occurred_at' => '2026-10-04',
            ])
            ->assertSessionHasErrors('destination_account_id');

        $this->from(route('transactions.create'))
            ->post(route('transactions.store'), [
                'type' => 'transfer',
                'account_id' => $source->id,
                'destination_account_id' => $differentCurrency->id,
                'amount' => '10,00',
                'occurred_at' => '2026-10-04',
            ])
            ->assertSessionHasErrors('destination_account_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_used_category_cannot_change_between_income_and_expense(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create(['type' => 'expense']);
        Transaction::factory()->for($user)->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
        ]);

        $this->actingAs($user)
            ->from(route('categories.edit', $category->id))
            ->put(route('categories.update', $category->id), [
                'name' => $category->name,
                'type' => 'income',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('type');

        $this->assertSame('expense', $category->fresh()->type->value);
    }

    public function test_users_cannot_open_another_users_finance_edit_pages(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignAccount = Account::factory()->for($otherUser)->create();
        $foreignCategory = Category::factory()->for($otherUser)->create();
        $foreignTransaction = Transaction::factory()->for($otherUser)->create([
            'account_id' => $foreignAccount->id,
            'category_id' => $foreignCategory->id,
        ]);

        $this->actingAs($user)
            ->get(route('accounts.edit', $foreignAccount->id))
            ->assertNotFound();

        $this->get(route('categories.edit', $foreignCategory->id))
            ->assertNotFound();

        $this->get(route('transactions.edit', $foreignTransaction->id))
            ->assertNotFound();
    }
}
