<?php

namespace App\Http\Controllers;

use App\Enums\BillType;
use App\Http\Requests\BillRequest;
use App\Models\Bill;
use App\Models\BillOccurrence;
use App\Services\BillOccurrenceGenerator;
use App\Support\MoneyAmount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BillController extends Controller
{
    public function index(Request $request, BillOccurrenceGenerator $generator): Response
    {
        Gate::authorize('viewAny', Bill::class);

        $request->user()->bills()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->each(fn (Bill $bill): int => $generator->generateFor($bill));

        $openOccurrences = $request->user()->billOccurrences()->whereNull('paid_at');
        $today = today();
        $weekEnd = today()->addDays(7);

        $upcoming = $request->user()->billOccurrences()
            ->with('bill:id,name,type')
            ->whereNull('paid_at')
            ->orderBy('due_date')
            ->limit(40)
            ->get()
            ->map(
                /**
                 * @return array{
                 *     id: int,
                 *     billId: int,
                 *     billName: string,
                 *     billType: string,
                 *     dueDate: string,
                 *     amountMinor: string,
                 *     overdue: bool
                 * }
                 */
                fn (BillOccurrence $occurrence): array => [
                    'id' => (int) $occurrence->id,
                    'billId' => (int) $occurrence->bill_id,
                    'billName' => $occurrence->bill->name,
                    'billType' => $occurrence->bill->type->value,
                    'dueDate' => $occurrence->due_date->toDateString(),
                    'amountMinor' => (string) $occurrence->amount_minor,
                    'overdue' => $occurrence->due_date->lt($today),
                ],
            )
            ->values();

        $recentPayments = $request->user()->billOccurrences()
            ->with(['bill:id,name,type', 'transaction.account:id,name,currency'])
            ->whereNotNull('paid_at')
            ->latest('paid_at')
            ->limit(10)
            ->get()
            ->map(
                /**
                 * @return array{
                 *     id: int,
                 *     billName: string,
                 *     billType: string,
                 *     dueDate: string,
                 *     paidAt: string,
                 *     amountMinor: string,
                 *     currency: string
                 * }
                 */
                fn (BillOccurrence $occurrence): array => [
                    'id' => (int) $occurrence->id,
                    'billName' => $occurrence->bill->name,
                    'billType' => $occurrence->bill->type->value,
                    'dueDate' => $occurrence->due_date->toDateString(),
                    'paidAt' => $occurrence->paid_at->toDateString(),
                    'amountMinor' => (string) $occurrence->paid_amount_minor,
                    'currency' => $occurrence->transaction->account->currency,
                ],
            )
            ->values();

        $bills = $request->user()->bills()
            ->withCount([
                'occurrences as paid_occurrences_count' => fn (Builder $query) => $query->whereNotNull('paid_at'),
            ])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(
                /**
                 * @return array{
                 *     id: int,
                 *     name: string,
                 *     type: string,
                 *     expectedAmountMinor: string,
                 *     firstDueOn: string,
                 *     repeatMonthly: bool,
                 *     endsOn: string|null,
                 *     isActive: bool,
                 *     paidOccurrencesCount: int,
                 *     canDelete: bool
                 * }
                 */
                fn (Bill $bill): array => [
                    'id' => (int) $bill->id,
                    'name' => $bill->name,
                    'type' => $bill->type->value,
                    'expectedAmountMinor' => (string) $bill->expected_amount_minor,
                    'firstDueOn' => $bill->first_due_on->toDateString(),
                    'repeatMonthly' => $bill->repeat_monthly,
                    'endsOn' => $bill->ends_on?->toDateString(),
                    'isActive' => $bill->is_active,
                    'paidOccurrencesCount' => (int) $bill->paid_occurrences_count,
                    'canDelete' => Gate::allows('delete', $bill),
                ],
            )
            ->values();

        return Inertia::render('bills/Index', [
            'bills' => $bills,
            'upcoming' => $upcoming,
            'recentPayments' => $recentPayments,
            'summary' => [
                'overdueCount' => (clone $openOccurrences)->whereDate('due_date', '<', $today)->count(),
                'dueSoonCount' => (clone $openOccurrences)
                    ->whereBetween('due_date', [$today->toDateString(), $weekEnd->toDateString()])
                    ->count(),
                'dueSoonMinor' => (string) (int) (clone $openOccurrences)
                    ->whereBetween('due_date', [$today->toDateString(), $weekEnd->toDateString()])
                    ->sum('amount_minor'),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Bill::class);

        return Inertia::render('bills/Form', [
            'bill' => null,
            'today' => today()->toDateString(),
        ]);
    }

    public function store(BillRequest $request, BillOccurrenceGenerator $generator): RedirectResponse
    {
        Gate::authorize('create', Bill::class);

        $data = $request->validated();

        DB::transaction(function () use ($request, $data, $generator): void {
            $bill = $request->user()->bills()->create([
                'name' => $data['name'],
                'type' => BillType::from($data['type']),
                'expected_amount_minor' => MoneyAmount::toMinor($data['expected_amount']),
                'first_due_on' => $data['first_due_on'],
                'repeat_monthly' => $data['repeat_monthly'],
                'ends_on' => $data['ends_on'] ?? null,
                'is_active' => true,
            ]);

            $generator->generateFor($bill);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fatura kaydedildi.']);

        return to_route('bills.index');
    }

    public function edit(Request $request, int $bill): Response
    {
        $bill = $request->user()->bills()->findOrFail($bill);
        Gate::authorize('update', $bill);

        return Inertia::render('bills/Form', [
            'bill' => [
                'id' => (int) $bill->id,
                'name' => $bill->name,
                'type' => $bill->type->value,
                'expectedAmount' => MoneyAmount::formatInput($bill->expected_amount_minor),
                'firstDueOn' => $bill->first_due_on->toDateString(),
                'repeatMonthly' => $bill->repeat_monthly,
                'endsOn' => $bill->ends_on?->toDateString(),
                'isActive' => $bill->is_active,
            ],
            'today' => today()->toDateString(),
        ]);
    }

    public function update(BillRequest $request, int $bill, BillOccurrenceGenerator $generator): RedirectResponse
    {
        $bill = $request->user()->bills()->findOrFail($bill);
        Gate::authorize('update', $bill);

        $data = $request->validated();

        DB::transaction(function () use ($request, $bill, $data, $generator): void {
            $bill->occurrences()
                ->whereNull('paid_at')
                ->whereDate('due_date', '>=', today())
                ->delete();

            $bill->update([
                'name' => $data['name'],
                'type' => BillType::from($data['type']),
                'expected_amount_minor' => MoneyAmount::toMinor($data['expected_amount']),
                'first_due_on' => $data['first_due_on'],
                'repeat_monthly' => $data['repeat_monthly'],
                'ends_on' => $data['ends_on'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            $generator->generateFor($bill->fresh());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fatura güncellendi.']);

        return to_route('bills.index');
    }

    public function destroy(Request $request, int $bill): RedirectResponse
    {
        DB::transaction(function () use ($request, $bill): void {
            $billRecord = $request->user()->bills()->lockForUpdate()->findOrFail($bill);
            $billRecord->occurrences()->lockForUpdate()->get(['id']);
            Gate::authorize('delete', $billRecord);
            $billRecord->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fatura silindi.']);

        return to_route('bills.index');
    }
}
