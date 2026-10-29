<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class FinanceRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_records_are_scoped_to_their_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $account = Account::factory()->for($owner)->create();
        $category = Category::factory()->for($owner)->create();
        $transaction = Transaction::factory()->for($owner)->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
        ]);

        Account::factory()->for($otherUser)->create();
        Category::factory()->for($otherUser)->create();
        Transaction::factory()->for($otherUser)->create();

        $this->assertCount(1, $owner->accounts);
        $this->assertCount(1, $owner->categories);
        $this->assertCount(1, $owner->transactions);
        $this->assertTrue(Gate::forUser($owner)->allows('view', $account));
        $this->assertTrue(Gate::forUser($owner)->allows('view', $category));
        $this->assertTrue(Gate::forUser($owner)->allows('view', $transaction));
        $this->assertFalse(Gate::forUser($otherUser)->allows('view', $account));
        $this->assertFalse(Gate::forUser($otherUser)->allows('view', $category));
        $this->assertFalse(Gate::forUser($otherUser)->allows('view', $transaction));
    }

    public function test_money_is_stored_as_integer_minor_units(): void
    {
        $transaction = Transaction::factory()->create([
            'amount_minor' => 12_550,
        ]);

        $this->assertSame(12_550, $transaction->fresh()->amount_minor);
    }

    public function test_transfers_link_source_and_destination_accounts(): void
    {
        $user = User::factory()->create();
        $source = Account::factory()->for($user)->create();
        $destination = Account::factory()->for($user)->create();

        $transfer = Transaction::factory()->for($user)->create([
            'account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'category_id' => null,
            'type' => TransactionType::Transfer,
        ]);

        $this->assertTrue($transfer->account->is($source));
        $this->assertTrue($transfer->destinationAccount->is($destination));
        $this->assertTrue($destination->incomingTransfers->contains($transfer));
        $this->assertFalse(Gate::forUser($user)->allows('delete', $source));
    }

    public function test_transaction_accounts_must_belong_to_the_transaction_owner(): void
    {
        $user = User::factory()->create();
        $otherUserAccount = Account::factory()->create();

        $this->expectException(QueryException::class);

        Transaction::factory()->for($user)->create([
            'account_id' => $otherUserAccount->id,
        ]);
    }

    public function test_transaction_categories_must_belong_to_the_transaction_owner(): void
    {
        $user = User::factory()->create();
        $otherUserCategory = Category::factory()->create();

        $this->expectException(QueryException::class);

        Transaction::factory()->for($user)->create([
            'category_id' => $otherUserCategory->id,
        ]);
    }

    public function test_transfer_destinations_must_belong_to_the_transaction_owner(): void
    {
        $user = User::factory()->create();
        $otherUserAccount = Account::factory()->create();

        $this->expectException(QueryException::class);

        Transaction::factory()->for($user)->create([
            'destination_account_id' => $otherUserAccount->id,
            'type' => TransactionType::Transfer,
            'category_id' => null,
        ]);
    }
}
