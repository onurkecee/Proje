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
 * @property int $loan_id
 * @property int $installment_number
 * @property Carbon $due_on
 * @property int $amount_minor
 * @property int $paid_amount_minor
 * @property Carbon|null $paid_at
 * @property Loan $loan
 */
#[Fillable(['user_id', 'installment_number', 'due_on', 'amount_minor', 'paid_amount_minor', 'paid_at'])]
class LoanInstallment extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Loan, $this>
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
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
            'installment_number' => 'integer',
            'due_on' => 'date',
            'amount_minor' => 'integer',
            'paid_amount_minor' => 'integer',
            'paid_at' => 'datetime',
        ];
    }
}
