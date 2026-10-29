<?php

namespace App\Support;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AccountBalanceSummary
{
    /**
     * @return Collection<int, array{
     *     id: int,
     *     name: string,
     *     type: 'bank'|'cash'|'credit_card',
     *     currency: string,
     *     openingBalanceMinor: decimal-int-string,
     *     balanceMinor: decimal-int-string,
     *     isActive: bool
     * }>
     */
    public function forUser(User $user): Collection
    {
        return $user->accounts()
            ->withSum(
                ['transactions as income_minor' => fn (Builder $query) => $query->where('type', TransactionType::Income->value)],
                'amount_minor',
            )
            ->withSum(
                ['transactions as expense_minor' => fn (Builder $query) => $query->where('type', TransactionType::Expense->value)],
                'amount_minor',
            )
            ->withSum(
                ['transactions as outgoing_transfer_minor' => fn (Builder $query) => $query->where('type', TransactionType::Transfer->value)],
                'amount_minor',
            )
            ->withSum(
                ['incomingTransfers as incoming_transfer_minor' => fn (Builder $query) => $query->where('type', TransactionType::Transfer->value)],
                'amount_minor',
            )
            ->orderBy('name')
            ->get()
            ->map(
                /**
                 * @return array{
                 *     id: int,
                 *     name: string,
                 *     type: 'bank'|'cash'|'credit_card',
                 *     currency: string,
                 *     openingBalanceMinor: decimal-int-string,
                 *     balanceMinor: decimal-int-string,
                 *     isActive: bool
                 * }
                 */
                fn (Account $account): array => [
                    'id' => (int) $account->id,
                    'name' => $account->name,
                    'type' => $account->type->value,
                    'currency' => $account->currency,
                    'openingBalanceMinor' => (string) $account->opening_balance_minor,
                    'balanceMinor' => (string) (
                        $account->opening_balance_minor
                        + (int) $account->getAttribute('income_minor')
                        - (int) $account->getAttribute('expense_minor')
                        - (int) $account->getAttribute('outgoing_transfer_minor')
                        + (int) $account->getAttribute('incoming_transfer_minor')
                    ),
                    'isActive' => $account->is_active,
                ],
            )
            ->values();
    }
}
