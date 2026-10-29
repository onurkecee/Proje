<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $account_id
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property Carbon $due_on
 * @property int $amount_minor
 * @property int $paid_amount_minor
 * @property Carbon|null $paid_at
 * @property Account $account
 * @property int $transactions_count
 */
#[Fillable(['user_id', 'account_id', 'period_start', 'period_end', 'due_on', 'amount_minor', 'paid_amount_minor', 'paid_at'])]
class CreditCardStatement extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'due_on' => 'date',
            'amount_minor' => 'integer',
            'paid_amount_minor' => 'integer',
            'paid_at' => 'datetime',
        ];
    }
}
