<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Http\Requests\TransactionRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\MoneyAmount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Transaction::class);

        $transactions = $request->user()->transactions()
            ->with([
                'account:id,name,currency',
                'category:id,name',
                'destinationAccount:id,name',
            ])
            ->withExists('billOccurrence')
            ->withExists('creditCardStatement')
            ->withExists('loanInstallment')
            ->latest('occurred_at')
            ->latest('id')
            ->paginate(20)
            ->through(
                /**
                 * @return array{
                 *     id: int,
                 *     type: string,
                 *     amountMinor: string,
                 *     description: string|null,
                 *     occurredAt: string,
                 *     account: array{id: int, name: string, currency: string},
                 *     category: array{id: int, name: string}|null,
                 *     destinationAccount: array{id: int, name: string}|null,
                 *     isBillPayment: bool,
                 *     isCreditCardPayment: bool,
                 *     isLoanPayment: bool
                 * }
                 */
                fn (Transaction $transaction): array => [
                    'id' => (int) $transaction->id,
                    'type' => $transaction->type->value,
                    'amountMinor' => (string) $transaction->amount_minor,
                    'description' => $transaction->description,
                    'occurredAt' => $transaction->occurred_at->toDateString(),
                    'account' => [
                        'id' => (int) $transaction->account->id,
                        'name' => $transaction->account->name,
                        'currency' => $transaction->account->currency,
                    ],
                    'category' => $transaction->category ? [
                        'id' => (int) $transaction->category->id,
                        'name' => $transaction->category->name,
                    ] : null,
                    'destinationAccount' => $transaction->destinationAccount ? [
                        'id' => (int) $transaction->destinationAccount->id,
                        'name' => $transaction->destinationAccount->name,
                    ] : null,
                    'isBillPayment' => (bool) $transaction->getAttribute('bill_occurrence_exists'),
                    'isCreditCardPayment' => (bool) $transaction->getAttribute('credit_card_statement_exists'),
                    'isLoanPayment' => (bool) $transaction->getAttribute('loan_installment_exists'),
                ],
            );

        return Inertia::render('transactions/Index', [
            'transactions' => $transactions,
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Transaction::class);

        return Inertia::render('transactions/Form', [
            ...$this->formOptions($request),
            'transaction' => null,
            'today' => now()->toDateString(),
        ]);
    }

    public function store(TransactionRequest $request): RedirectResponse
    {
        Gate::authorize('create', Transaction::class);

        $data = $request->validated();
        $type = TransactionType::from($data['type']);

        $request->user()->transactions()->create([
            'account_id' => (int) $data['account_id'],
            'destination_account_id' => $type === TransactionType::Transfer
                ? (int) $data['destination_account_id']
                : null,
            'category_id' => $type === TransactionType::Transfer ? null : (int) $data['category_id'],
            'type' => $type,
            'amount_minor' => MoneyAmount::toMinor($data['amount']),
            'description' => $data['description'] ?? null,
            'occurred_at' => $data['occurred_at'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hareket kaydedildi.']);

        return to_route('transactions.index');
    }

    public function edit(Request $request, int $transaction): Response
    {
        $transaction = $request->user()->transactions()->findOrFail($transaction);
        Gate::authorize('update', $transaction);

        return Inertia::render('transactions/Form', [
            ...$this->formOptions($request, $transaction),
            'transaction' => [
                'id' => (int) $transaction->id,
                'type' => $transaction->type->value,
                'accountId' => $transaction->account_id,
                'destinationAccountId' => $transaction->destination_account_id,
                'categoryId' => $transaction->category_id,
                'amount' => MoneyAmount::formatInput((int) $transaction->amount_minor),
                'description' => $transaction->description,
                'occurredAt' => $transaction->occurred_at->toDateString(),
            ],
            'today' => now()->toDateString(),
        ]);
    }

    public function update(TransactionRequest $request, int $transaction): RedirectResponse
    {
        $transaction = $request->user()->transactions()->findOrFail($transaction);
        Gate::authorize('update', $transaction);

        $data = $request->validated();
        $type = TransactionType::from($data['type']);

        $transaction->update([
            'account_id' => (int) $data['account_id'],
            'destination_account_id' => $type === TransactionType::Transfer
                ? (int) $data['destination_account_id']
                : null,
            'category_id' => $type === TransactionType::Transfer ? null : (int) $data['category_id'],
            'type' => $type,
            'amount_minor' => MoneyAmount::toMinor($data['amount']),
            'description' => $data['description'] ?? null,
            'occurred_at' => $data['occurred_at'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hareket güncellendi.']);

        return to_route('transactions.index');
    }

    public function destroy(Request $request, int $transaction): RedirectResponse
    {
        $transaction = $request->user()->transactions()->findOrFail($transaction);
        Gate::authorize('delete', $transaction);
        $transaction->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hareket silindi.']);

        return to_route('transactions.index');
    }

    /**
     * @return array{
     *     accounts: Collection<int, array{id: int, name: string, currency: string}>,
     *     categories: Collection<int, array{id: int, name: string, type: 'income'|'expense'}>
     * }
     */
    private function formOptions(Request $request, ?Transaction $transaction = null): array
    {
        $accountIds = $transaction === null
            ? []
            : array_values(array_filter([$transaction->account_id, $transaction->destination_account_id]));

        return [
            'accounts' => $request->user()->accounts()
                ->where(function (Builder $query) use ($accountIds): void {
                    $query->where('is_active', true);

                    if ($accountIds !== []) {
                        $query->orWhereIn('id', $accountIds);
                    }
                })
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
                ->where(function (Builder $query) use ($transaction): void {
                    $query->where('is_active', true);

                    if ($transaction?->category_id !== null) {
                        $query->orWhere('id', $transaction->category_id);
                    }
                })
                ->whereIn('type', [CategoryType::Income->value, CategoryType::Expense->value])
                ->orderBy('name')
                ->get(['id', 'name', 'type'])
                ->map(
                    /** @return array{id: int, name: string, type: string} */
                    fn (Category $category): array => [
                        'id' => (int) $category->id,
                        'name' => $category->name,
                        'type' => $category->type->value,
                    ],
                )
                ->values(),
        ];
    }
}
