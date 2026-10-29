<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $bill_id
 * @property Carbon $due_date
 * @property int $amount_minor
 * @property Carbon|null $paid_at
 * @property int|null $paid_amount_minor
 */
#[Fillable(['user_id', 'due_date', 'amount_minor', 'paid_at', 'paid_amount_minor'])]
class BillOccurrence extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Bill, $this>
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * @return HasOne<Transaction, $this>
     */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount_minor' => 'integer',
            'paid_at' => 'datetime',
            'paid_amount_minor' => 'integer',
        ];
    }
}
