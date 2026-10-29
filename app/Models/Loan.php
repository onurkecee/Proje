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
 * @property string $name
 * @property string|null $lender
 * @property int $total_repayment_minor
 * @property int $installment_amount_minor
 * @property int $installment_count
 * @property Carbon $first_due_on
 * @property bool $is_active
 * @property int $installments_count
 */
#[Fillable(['name', 'lender', 'total_repayment_minor', 'installment_amount_minor', 'installment_count', 'first_due_on', 'is_active'])]
class Loan extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<LoanInstallment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_repayment_minor' => 'integer',
            'installment_amount_minor' => 'integer',
            'installment_count' => 'integer',
            'first_due_on' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
