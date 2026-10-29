<?php

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $account_id
 * @property int|null $destination_account_id
 * @property int|null $category_id
 * @property int|null $bill_occurrence_id
 * @property int|null $credit_card_statement_id
 * @property int|null $loan_installment_id
 * @property TransactionType $type
 * @property int $amount_minor
 * @property string|null $description
 * @property Carbon $occurred_at
 */
#[Fillable(['account_id', 'destination_account_id', 'category_id', 'bill_occurrence_id', 'credit_card_statement_id', 'loan_installment_id', 'type', 'amount_minor', 'description', 'occurred_at'])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The account from which an expense or transfer is debited.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * The account credited by a transfer.
     *
     * @return BelongsTo<Account, $this>
     */
    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<BillOccurrence, $this>
     */
    public function billOccurrence(): BelongsTo
    {
        return $this->belongsTo(BillOccurrence::class);
    }

    /**
     * @return BelongsTo<CreditCardStatement, $this>
     */
    public function creditCardStatement(): BelongsTo
    {
        return $this->belongsTo(CreditCardStatement::class);
    }

    /**
     * @return BelongsTo<LoanInstallment, $this>
     */
    public function loanInstallment(): BelongsTo
    {
        return $this->belongsTo(LoanInstallment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount_minor' => 'integer',
            'occurred_at' => 'date',
        ];
    }
}
