<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Models\BillOccurrence;
use App\Models\CreditCardStatement;
use App\Models\LoanInstallment;
use App\Models\Transaction;
use App\Models\User;
use App\Support\AccountBalanceSummary;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;

class DashboardController extends Controller
{
    public function index(Request $request, AccountBalanceSummary $balanceSummary): Response
    {
        Gate::authorize('viewAny', Transaction::class);

        $validated = $request->validate([
            'month' => ['sometimes', 'required', 'date_format:Y-m'],
        ]);
        $month = CarbonImmutable::parse(($validated['month'] ?? today()->format('Y-m')).'-01');
        $start = $month->startOfMonth();
        $end = $month->endOfMonth();
        $previousStart = $start->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $previousStart->endOfMonth();
        $user = $request->user();
        $accounts = $balanceSummary->forUser($user);

        $currentTotals = $this->totalsForPeriod($user, $start, $end);
        $previousTotals = $this->totalsForPeriod($user, $previousStart, $previousEnd);
        $trend = $this->monthlyTrend($user, $start);
        $maxTrendMinor = collect($trend)
            ->flatMap(fn (array $item): array => [$item['incomeMinor'], $item['expenseMinor']])
            ->map(fn (string $value): int => (int) $value)
            ->max() ?: 0;

        $categoryAmounts = DB::table('transactions')
            ->join('categories', function ($join): void {
                $join->on('categories.id', '=', 'transactions.category_id')
                    ->on('categories.user_id', '=', 'transactions.user_id');
            })
            ->where('transactions.user_id', $user->id)
            ->where('transactions.type', TransactionType::Expense->value)
            ->whereBetween('transactions.occurred_at', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('categories.id as category_id, categories.name as category_name, SUM(transactions.amount_minor) as amount_minor')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('amount_minor')
            ->get();

        $topCategories = $categoryAmounts->take(5);
        $otherCategoriesMinor = (int) $categoryAmounts->slice(5)->sum(fn (stdClass $category): int => (int) $category->amount_minor);
        $categories = $topCategories
            ->map(
                /** @return array{id: int, name: string, amountMinor: string} */
                fn (stdClass $category): array => [
                    'id' => (int) $category->category_id,
                    'name' => $category->category_name,
                    'amountMinor' => (string) $category->amount_minor,
                ],
            )
            ->values()
            ->all();

        if ($otherCategoriesMinor > 0) {
            $categories[] = [
                'id' => 0,
                'name' => 'Diğer kategoriler',
                'amountMinor' => (string) $otherCategoriesMinor,
            ];
        }

        $recentTransactions = $user->transactions()
            ->with([
                'account:id,name,currency',
                'category:id,name',
                'destinationAccount:id,name',
            ])
            ->withExists(['billOccurrence', 'creditCardStatement', 'loanInstallment'])
            ->latest('occurred_at')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(
                /**
                 * @return array{
                 *     id: int,
                 *     type: string,
                 *     amountMinor: string,
                 *     description: string|null,
                 *     occurredAt: string,
                 *     accountName: string,
                 *     currency: string,
                 *     categoryName: string|null,
                 *     destinationAccountName: string|null,
                 *     isLinkedPayment: bool
                 * }
                 */
                fn (Transaction $transaction): array => [
                    'id' => (int) $transaction->id,
                    'type' => $transaction->type->value,
                    'amountMinor' => (string) $transaction->amount_minor,
                    'description' => $transaction->description,
                    'occurredAt' => $transaction->occurred_at->toDateString(),
                    'accountName' => $transaction->account->name,
                    'currency' => $transaction->account->currency,
                    'categoryName' => $transaction->category?->name,
                    'destinationAccountName' => $transaction->destinationAccount?->name,
                    'isLinkedPayment' => (bool) $transaction->getAttribute('bill_occurrence_exists')
                        || (bool) $transaction->getAttribute('credit_card_statement_exists')
                        || (bool) $transaction->getAttribute('loan_installment_exists'),
                ],
            )
            ->values()
            ->all();

        $upcomingPayments = $this->upcomingPayments($user);
        $currentMonth = CarbonImmutable::now()->startOfMonth();

        return Inertia::render('Dashboard', [
            'month' => $start->format('Y-m'),
            'previousMonth' => $start->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $start->addMonthNoOverflow()->format('Y-m'),
            'canGoNext' => $start->lessThan($currentMonth),
            'summary' => [
                'incomeMinor' => $currentTotals['incomeMinor'],
                'expenseMinor' => $currentTotals['expenseMinor'],
                'netMinor' => (string) ((int) $currentTotals['incomeMinor'] - (int) $currentTotals['expenseMinor']),
                'previousIncomeMinor' => $previousTotals['incomeMinor'],
                'previousExpenseMinor' => $previousTotals['expenseMinor'],
                'netWorthMinor' => (string) $accounts->sum(fn (array $account): int => (int) $account['balanceMinor']),
                'accountCount' => $accounts->count(),
                'transactionCount' => $currentTotals['transactionCount'],
            ],
            'trend' => $trend,
            'trendMaxMinor' => (string) $maxTrendMinor,
            'categories' => $categories,
            'categoryTotalMinor' => $currentTotals['expenseMinor'],
            'accounts' => $accounts,
            'recentTransactions' => $recentTransactions,
            'upcomingPayments' => $upcomingPayments,
        ]);
    }

    /**
     * @return array{incomeMinor: string, expenseMinor: string, transactionCount: int}
     */
    private function totalsForPeriod(User $user, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $totals = DB::table('transactions')
            ->where('user_id', $user->id)
            ->whereBetween('occurred_at', [$start->toDateString(), $end->toDateString()])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type = ? THEN amount_minor ELSE 0 END), 0) as income_minor,
                COALESCE(SUM(CASE WHEN type = ? THEN amount_minor ELSE 0 END), 0) as expense_minor,
                COUNT(CASE WHEN type IN (?, ?) THEN 1 END) as transaction_count',
                [
                    TransactionType::Income->value,
                    TransactionType::Expense->value,
                    TransactionType::Income->value,
                    TransactionType::Expense->value,
                ],
            )
            ->first();

        return [
            'incomeMinor' => (string) (int) $totals?->income_minor,
            'expenseMinor' => (string) (int) $totals?->expense_minor,
            'transactionCount' => (int) $totals?->transaction_count,
        ];
    }

    /**
     * @return array<int, array{month: string, incomeMinor: string, expenseMinor: string}>
     */
    private function monthlyTrend(User $user, CarbonImmutable $selectedMonth): array
    {
        $trend = [];
        $firstMonth = $selectedMonth->startOfMonth()->subMonthsNoOverflow(5);

        for ($offset = 0; $offset < 6; $offset++) {
            $month = $firstMonth->addMonthsNoOverflow($offset);
            $totals = $this->totalsForPeriod($user, $month, $month->endOfMonth());
            $trend[] = [
                'month' => $month->format('Y-m'),
                'incomeMinor' => $totals['incomeMinor'],
                'expenseMinor' => $totals['expenseMinor'],
            ];
        }

        return $trend;
    }

    /**
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     type: string,
     *     dueOn: string,
     *     amountMinor: string,
     *     currency: string,
     *     overdue: bool,
     *     href: string
     * }>
     */
    private function upcomingPayments(User $user): array
    {
        $payments = $user->billOccurrences()
            ->whereNull('paid_at')
            ->whereHas('bill', fn (Builder $query) => $query->where('is_active', true))
            ->with('bill:id,name')
            ->whereDate('due_date', '<=', today()->addDays(30))
            ->orderBy('due_date')
            ->limit(8)
            ->get()
            ->map(
                /**
                 * @return array{
                 *     id: string,
                 *     name: string,
                 *     type: 'bill',
                 *     dueOn: string,
                 *     amountMinor: string,
                 *     currency: string,
                 *     overdue: bool,
                 *     href: string
                 * }
                 */
                fn (BillOccurrence $occurrence): array => [
                    'id' => 'bill-'.$occurrence->id,
                    'name' => $occurrence->bill->name,
                    'type' => 'bill',
                    'dueOn' => $occurrence->due_date->toDateString(),
                    'amountMinor' => (string) $occurrence->amount_minor,
                    'currency' => 'TRY',
                    'overdue' => $occurrence->due_date->lt(today()),
                    'href' => route('bills.occurrences.create', [
                        'bill' => $occurrence->bill_id,
                        'occurrence' => $occurrence->id,
                    ]),
                ],
            );

        $statements = $user->creditCardStatements()
            ->whereNull('paid_at')
            ->whereColumn('paid_amount_minor', '<', 'amount_minor')
            ->whereDate('due_on', '<=', today()->addDays(30))
            ->with('account:id,name,currency')
            ->orderBy('due_on')
            ->limit(8)
            ->get()
            ->map(
                /**
                 * @return array{
                 *     id: string,
                 *     name: string,
                 *     type: 'credit_card',
                 *     dueOn: string,
                 *     amountMinor: string,
                 *     currency: string,
                 *     overdue: bool,
                 *     href: string
                 * }
                 */
                fn (CreditCardStatement $statement): array => [
                    'id' => 'card-'.$statement->id,
                    'name' => $statement->account->name.' ekstresi',
                    'type' => 'credit_card',
                    'dueOn' => $statement->due_on->toDateString(),
                    'amountMinor' => (string) ($statement->amount_minor - $statement->paid_amount_minor),
                    'currency' => $statement->account->currency,
                    'overdue' => $statement->due_on->lt(today()),
                    'href' => route('credit-card-statements.pay.create', $statement->id),
                ],
            );

        $installments = $user->loanInstallments()
            ->whereNull('paid_at')
            ->whereColumn('paid_amount_minor', '<', 'amount_minor')
            ->whereDate('due_on', '<=', today()->addDays(30))
            ->with('loan:id,name')
            ->orderBy('due_on')
            ->limit(8)
            ->get()
            ->map(
                /**
                 * @return array{
                 *     id: string,
                 *     name: string,
                 *     type: 'loan',
                 *     dueOn: string,
                 *     amountMinor: string,
                 *     currency: string,
                 *     overdue: bool,
                 *     href: string
                 * }
                 */
                fn (LoanInstallment $installment): array => [
                    'id' => 'loan-'.$installment->id,
                    'name' => $installment->loan->name.' · '.$installment->installment_number.'. taksit',
                    'type' => 'loan',
                    'dueOn' => $installment->due_on->toDateString(),
                    'amountMinor' => (string) ($installment->amount_minor - $installment->paid_amount_minor),
                    'currency' => 'TRY',
                    'overdue' => $installment->due_on->lt(today()),
                    'href' => route('loans.installments.pay.create', [
                        'loan' => $installment->loan_id,
                        'installment' => $installment->id,
                    ]),
                ],
            );

        return $payments
            ->concat($statements)
            ->concat($installments)
            ->sortBy('dueOn')
            ->take(8)
            ->values()
            ->all();
    }
}
