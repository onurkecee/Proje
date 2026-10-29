<?php

namespace App\Models;

use App\Enums\AccountType;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property AccountType $type
 * @property string $currency
 * @property int $opening_balance_minor
 * @property bool $is_active
 */
#[Fillable(['name', 'type', 'currency', 'opening_balance_minor', 'is_active'])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Transactions that debit this account.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Transfers that credit this account.
     *
     * @return HasMany<Transaction, $this>
     */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'destination_account_id');
    }

    /**
     * @return HasMany<CreditCardStatement, $this>
     */
    public function creditCardStatements(): HasMany
    {
        return $this->hasMany(CreditCardStatement::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'opening_balance_minor' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
