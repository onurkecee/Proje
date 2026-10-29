<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoanRequest;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Services\LoanInstallmentGenerator;
use App\Support\MoneyAmount;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Loan::class);

        $loans = $request->user()->loans()
            ->with(['installments' => fn (HasMany $query) => $query->orderBy('installment_number')])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(
                /**
                 * @return array{
                 *     id: int,
                 *     name: string,
                 *     lender: string|null,
                 *     totalRepaymentMinor: string,
                 *     installmentAmountMinor: string,
                 *     installmentCount: int,
                 *     firstDueOn: string,
                 *     isActive: bool,
                 *     paidInstallments: int,
                 *     paidMinor: string,
                 *     remainingMinor: string,
                 *     nextInstallment: array{id: int, number: int, dueOn: string, amountMinor: string, remainingMinor: string}|null,
                 *     canEdit: bool
                 * }
                 */
                function (Loan $loan): array {
                    $paidMinor = $loan->installments->sum('paid_amount_minor');
                    $nextInstallment = $loan->installments
                        ->first(fn (LoanInstallment $installment): bool => $installment->paid_at === null);

                    return [
                        'id' => (int) $loan->id,
                        'name' => $loan->name,
                        'lender' => $loan->lender,
                        'totalRepaymentMinor' => (string) $loan->total_repayment_minor,
                        'installmentAmountMinor' => (string) $loan->installment_amount_minor,
                        'installmentCount' => $loan->installment_count,
                        'firstDueOn' => $loan->first_due_on->toDateString(),
                        'isActive' => $loan->is_active,
                        'paidInstallments' => $loan->installments->whereNotNull('paid_at')->count(),
                        'paidMinor' => (string) $paidMinor,
                        'remainingMinor' => (string) max(0, $loan->total_repayment_minor - $paidMinor),
                        'nextInstallment' => $nextInstallment ? [
                            'id' => (int) $nextInstallment->id,
                            'number' => $nextInstallment->installment_number,
                            'dueOn' => $nextInstallment->due_on->toDateString(),
                            'amountMinor' => (string) $nextInstallment->amount_minor,
                            'remainingMinor' => (string) max(0, $nextInstallment->amount_minor - $nextInstallment->paid_amount_minor),
                        ] : null,
                        'canEdit' => Gate::allows('update', $loan),
                    ];
                },
            )
            ->values();

        $installments = $request->user()->loanInstallments()
            ->with('loan:id,name,lender')
            ->orderBy('due_on')
            ->get()
            ->map(
                /**
                 * @return array{
                 *     id: int,
                 *     loanId: int,
                 *     loanName: string,
                 *     installmentNumber: int,
                 *     dueOn: string,
                 *     amountMinor: string,
                 *     paidAmountMinor: string,
                 *     remainingMinor: string,
                 *     paid: bool,
                 *     overdue: bool
                 * }
                 */
                fn (LoanInstallment $installment): array => [
                    'id' => (int) $installment->id,
                    'loanId' => (int) $installment->loan_id,
                    'loanName' => $installment->loan->name,
                    'installmentNumber' => $installment->installment_number,
                    'dueOn' => $installment->due_on->toDateString(),
                    'amountMinor' => (string) $installment->amount_minor,
                    'paidAmountMinor' => (string) $installment->paid_amount_minor,
                    'remainingMinor' => (string) max(0, $installment->amount_minor - $installment->paid_amount_minor),
                    'paid' => $installment->paid_at !== null,
                    'overdue' => $installment->paid_at === null && $installment->due_on->lt(today()),
                ],
            )
            ->values();

        return Inertia::render('loans/Index', [
            'loans' => $loans,
            'installments' => $installments,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Loan::class);

        return Inertia::render('loans/Form', [
            'loan' => null,
            'today' => today()->toDateString(),
        ]);
    }

    public function store(LoanRequest $request, LoanInstallmentGenerator $generator): RedirectResponse
    {
        Gate::authorize('create', Loan::class);

        $data = $request->validated();

        DB::transaction(function () use ($request, $data, $generator): void {
            $loan = $request->user()->loans()->create([
                'name' => $data['name'],
                'lender' => $data['lender'] ?? null,
                'total_repayment_minor' => MoneyAmount::toMinor($data['total_repayment']),
                'installment_amount_minor' => MoneyAmount::toMinor($data['installment_amount']),
                'installment_count' => (int) $data['installment_count'],
                'first_due_on' => $data['first_due_on'],
                'is_active' => true,
            ]);

            $generator->generateFor($loan);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kredi ve taksit planı oluşturuldu.']);

        return to_route('loans.index');
    }

    public function edit(Request $request, int $loan): Response
    {
        $loan = $request->user()->loans()->findOrFail($loan);
        Gate::authorize('update', $loan);

        return Inertia::render('loans/Form', [
            'loan' => [
                'id' => (int) $loan->id,
                'name' => $loan->name,
                'lender' => $loan->lender,
                'totalRepayment' => MoneyAmount::formatInput($loan->total_repayment_minor),
                'installmentAmount' => MoneyAmount::formatInput($loan->installment_amount_minor),
                'installmentCount' => $loan->installment_count,
                'firstDueOn' => $loan->first_due_on->toDateString(),
                'isActive' => $loan->is_active,
            ],
            'today' => today()->toDateString(),
        ]);
    }

    public function update(LoanRequest $request, int $loan, LoanInstallmentGenerator $generator): RedirectResponse
    {
        $loan = $request->user()->loans()->findOrFail($loan);
        Gate::authorize('update', $loan);

        $data = $request->validated();

        DB::transaction(function () use ($request, $loan, $data, $generator): void {
            $loanRecord = $request->user()->loans()->lockForUpdate()->findOrFail($loan->id);
            Gate::authorize('update', $loanRecord);
            $loanRecord->installments()->delete();
            $loanRecord->update([
                'name' => $data['name'],
                'lender' => $data['lender'] ?? null,
                'total_repayment_minor' => MoneyAmount::toMinor($data['total_repayment']),
                'installment_amount_minor' => MoneyAmount::toMinor($data['installment_amount']),
                'installment_count' => (int) $data['installment_count'],
                'first_due_on' => $data['first_due_on'],
                'is_active' => $request->boolean('is_active'),
            ]);
            $generator->generateFor($loanRecord->refresh());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kredi planı güncellendi.']);

        return to_route('loans.index');
    }

    public function destroy(Request $request, int $loan): RedirectResponse
    {
        DB::transaction(function () use ($request, $loan): void {
            $loanRecord = $request->user()->loans()->lockForUpdate()->findOrFail($loan);
            Gate::authorize('delete', $loanRecord);
            $loanRecord->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kredi planı silindi.']);

        return to_route('loans.index');
    }
}
