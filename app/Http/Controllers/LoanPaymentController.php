<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Http\Requests\LoanPaymentRequest;
use App\Models\Account;
use App\Models\Category;
use App\Support\MoneyAmount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoanPaymentController extends Controller
{
    public function create(Request $request, int $loan, int $installment): Response
    {
        $loan = $request->user()->loans()->findOrFail($loan);
        Gate::authorize('view', $loan);

        $installment = $loan->installments()
            ->whereNull('paid_at')
            ->findOrFail($installment);

        return Inertia::render('loans/Payment', [
            'loan' => [
                'id' => (int) $loan->id,
                'name' => $loan->name,
                'lender' => $loan->lender,
            ],
            'installment' => [
                'id' => (int) $installment->id,
                'number' => $installment->installment_number,
                'dueOn' => $installment->due_on->toDateString(),
                'amountMinor' => (string) $installment->amount_minor,
                'paidAmountMinor' => (string) $installment->paid_amount_minor,
                'remainingMinor' => (string) ($installment->amount_minor - $installment->paid_amount_minor),
                'amount' => MoneyAmount::formatInput($installment->amount_minor - $installment->paid_amount_minor),
            ],
            'accounts' => $request->user()->accounts()
                ->where('is_active', true)
                ->where('currency', 'TRY')
                ->orderBy('name')
                ->get(['id', 'name', 'currency'])
                ->map(
                    /** @return array{id: int, name: string, currency: string} */
                    fn (Account $account): array => [
                        'id' => (int) $account->id,
                        'name' => $account->name,
                        'currency' => $account->currency,
                    ],
                )
                ->values(),
            'categories' => $request->user()->categories()
                ->where('type', CategoryType::Expense->value)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(
                    /** @return array{id: int, name: string} */
                    fn (Category $category): array => [
                        'id' => (int) $category->id,
                        'name' => $category->name,
                    ],
                )
                ->values(),
            'today' => today()->toDateString(),
        ]);
    }

    public function store(LoanPaymentRequest $request, int $loan, int $installment): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $loan, $installment, $data): void {
            $loanRecord = $request->user()->loans()->lockForUpdate()->findOrFail($loan);
            Gate::authorize('view', $loanRecord);
            $loanInstallment = $loanRecord->installments()
                ->whereNull('paid_at')
                ->lockForUpdate()
                ->findOrFail($installment);
            $amountMinor = MoneyAmount::toMinor($data['amount']);
            $remainingMinor = $loanInstallment->amount_minor - $loanInstallment->paid_amount_minor;

            if ($amountMinor > $remainingMinor) {
                throw ValidationException::withMessages([
                    'amount' => 'Ödeme tutarı taksit kalanını aşamaz.',
                ]);
            }

            $request->user()->transactions()->create([
                'account_id' => (int) $data['account_id'],
                'category_id' => (int) $data['category_id'],
                'loan_installment_id' => (int) $loanInstallment->id,
                'type' => TransactionType::Expense,
                'amount_minor' => $amountMinor,
                'description' => 'Kredi taksiti: '.$loanRecord->name.' ('.$loanInstallment->installment_number.'. taksit)',
                'occurred_at' => $data['paid_at'],
            ]);

            $paidAmountMinor = $loanInstallment->paid_amount_minor + $amountMinor;
            $loanInstallment->update([
                'paid_amount_minor' => $paidAmountMinor,
                'paid_at' => $paidAmountMinor === $loanInstallment->amount_minor ? $data['paid_at'] : null,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kredi taksiti gider hareketi olarak kaydedildi.']);

        return to_route('loans.index');
    }
}
