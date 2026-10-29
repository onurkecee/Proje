<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Http\Requests\BillPaymentRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\MoneyAmount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BillPaymentController extends Controller
{
    public function create(Request $request, int $bill, int $occurrence): Response
    {
        $bill = $request->user()->bills()->findOrFail($bill);
        Gate::authorize('view', $bill);

        $occurrence = $bill->occurrences()
            ->whereNull('paid_at')
            ->findOrFail($occurrence);

        return Inertia::render('bills/Payment', [
            'bill' => [
                'id' => (int) $bill->id,
                'name' => $bill->name,
                'type' => $bill->type->value,
            ],
            'occurrence' => [
                'id' => (int) $occurrence->id,
                'dueDate' => $occurrence->due_date->toDateString(),
                'amount' => MoneyAmount::formatInput($occurrence->amount_minor),
                'amountMinor' => (string) $occurrence->amount_minor,
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

    public function store(BillPaymentRequest $request, int $bill, int $occurrence): RedirectResponse
    {
        $bill = $request->user()->bills()->findOrFail($bill);
        Gate::authorize('view', $bill);

        $data = $request->validated();

        DB::transaction(function () use ($request, $bill, $occurrence, $data): Transaction {
            $billOccurrence = $bill->occurrences()
                ->whereNull('paid_at')
                ->lockForUpdate()
                ->findOrFail($occurrence);

            $amountMinor = MoneyAmount::toMinor($data['amount']);
            $transaction = $request->user()->transactions()->create([
                'account_id' => (int) $data['account_id'],
                'category_id' => (int) $data['category_id'],
                'bill_occurrence_id' => (int) $billOccurrence->id,
                'type' => TransactionType::Expense,
                'amount_minor' => $amountMinor,
                'description' => 'Fatura ödemesi: '.$bill->name,
                'occurred_at' => $data['paid_at'],
            ]);

            $billOccurrence->update([
                'paid_at' => $data['paid_at'],
                'paid_amount_minor' => $amountMinor,
            ]);

            return $transaction;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fatura ödendi ve gider hareketi oluşturuldu.']);

        return to_route('bills.index');
    }
}
