<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\CreditCardStatementRequest;
use App\Models\Account;
use App\Models\CreditCardStatement;
use App\Support\MoneyAmount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CreditCardStatementController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', CreditCardStatement::class);

        $statements = $request->user()->creditCardStatements()
            ->with('account:id,name,currency')
            ->orderByDesc('due_on')
            ->orderByDesc('id')
            ->get()
            ->map(
                /**
                 * @return array{
                 *     id: int,
                 *     accountId: int,
                 *     cardName: string,
                 *     currency: string,
                 *     periodStart: string,
                 *     periodEnd: string,
                 *     dueOn: string,
                 *     amountMinor: string,
                 *     paidAmountMinor: string,
                 *     remainingMinor: string,
                 *     paid: bool,
                 *     overdue: bool,
                 *     canEdit: bool
                 * }
                 */
                fn (CreditCardStatement $statement): array => [
                    'id' => (int) $statement->id,
                    'accountId' => (int) $statement->account_id,
                    'cardName' => $statement->account->name,
                    'currency' => $statement->account->currency,
                    'periodStart' => $statement->period_start->toDateString(),
                    'periodEnd' => $statement->period_end->toDateString(),
                    'dueOn' => $statement->due_on->toDateString(),
                    'amountMinor' => (string) $statement->amount_minor,
                    'paidAmountMinor' => (string) $statement->paid_amount_minor,
                    'remainingMinor' => (string) ($statement->amount_minor - $statement->paid_amount_minor),
                    'paid' => $statement->paid_at !== null,
                    'overdue' => $statement->paid_at === null && $statement->due_on->lt(today()),
                    'canEdit' => Gate::allows('update', $statement),
                ],
            )
            ->values();

        $openStatements = $request->user()->creditCardStatements()->whereNull('paid_at')->get();
        $dueSoon = $openStatements->filter(
            fn (CreditCardStatement $statement): bool => $statement->due_on->betweenIncluded(today(), today()->addDays(7)),
        );

        return Inertia::render('credit-cards/Index', [
            'statements' => $statements,
            'cards' => $request->user()->accounts()
                ->where('type', AccountType::CreditCard->value)
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
            'summary' => [
                'openCount' => $openStatements->count(),
                'overdueCount' => $openStatements->filter(fn (CreditCardStatement $statement): bool => $statement->due_on->lt(today()))->count(),
                'dueSoonMinor' => (string) $dueSoon->sum(fn (CreditCardStatement $statement): int => $statement->amount_minor - $statement->paid_amount_minor),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', CreditCardStatement::class);

        return Inertia::render('credit-cards/Form', [
            'statement' => null,
            'cards' => $this->cardOptions($request),
            'today' => today()->toDateString(),
        ]);
    }

    public function store(CreditCardStatementRequest $request): RedirectResponse
    {
        Gate::authorize('create', CreditCardStatement::class);

        $data = $request->validated();
        $request->user()->creditCardStatements()->create([
            'account_id' => (int) $data['account_id'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'due_on' => $data['due_on'],
            'amount_minor' => MoneyAmount::toMinor($data['amount']),
            'paid_amount_minor' => 0,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kart ekstresi kaydedildi.']);

        return to_route('credit-card-statements.index');
    }

    public function edit(Request $request, int $credit_card_statement): Response
    {
        $statement = $request->user()->creditCardStatements()->findOrFail($credit_card_statement);
        Gate::authorize('update', $statement);

        return Inertia::render('credit-cards/Form', [
            'statement' => [
                'id' => (int) $statement->id,
                'accountId' => (int) $statement->account_id,
                'periodStart' => $statement->period_start->toDateString(),
                'periodEnd' => $statement->period_end->toDateString(),
                'dueOn' => $statement->due_on->toDateString(),
                'amount' => MoneyAmount::formatInput($statement->amount_minor),
            ],
            'cards' => $this->cardOptions($request, $statement->account_id),
            'today' => today()->toDateString(),
        ]);
    }

    public function update(CreditCardStatementRequest $request, int $credit_card_statement): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $credit_card_statement, $data): void {
            $statement = $request->user()->creditCardStatements()->lockForUpdate()->findOrFail($credit_card_statement);
            Gate::authorize('update', $statement);
            $statement->update([
                'account_id' => (int) $data['account_id'],
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'due_on' => $data['due_on'],
                'amount_minor' => MoneyAmount::toMinor($data['amount']),
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kart ekstresi güncellendi.']);

        return to_route('credit-card-statements.index');
    }

    public function destroy(Request $request, int $credit_card_statement): RedirectResponse
    {
        DB::transaction(function () use ($request, $credit_card_statement): void {
            $statement = $request->user()->creditCardStatements()->lockForUpdate()->findOrFail($credit_card_statement);
            Gate::authorize('delete', $statement);
            $statement->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kart ekstresi silindi.']);

        return to_route('credit-card-statements.index');
    }

    /**
     * @return array<int, array{id: int, name: string, currency: string}>
     */
    private function cardOptions(Request $request, ?int $selectedId = null): array
    {
        return $request->user()->accounts()
            ->where('type', AccountType::CreditCard->value)
            ->where(function ($query) use ($selectedId): void {
                $query->where('is_active', true);

                if ($selectedId !== null) {
                    $query->orWhere('id', $selectedId);
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
            ->values()
            ->all();
    }
}
