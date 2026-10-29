<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Http\Requests\CreditCardPaymentRequest;
use App\Models\Account;
use App\Support\MoneyAmount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CreditCardPaymentController extends Controller
{
    public function create(Request $request, int $credit_card_statement): Response
    {
        $statement = $request->user()->creditCardStatements()
            ->with('account:id,name,currency')
            ->findOrFail($credit_card_statement);
        Gate::authorize('view', $statement);

        abort_if($statement->paid_amount_minor >= $statement->amount_minor, 404);

        return Inertia::render('credit-cards/Payment', [
            'statement' => [
                'id' => (int) $statement->id,
                'cardName' => $statement->account->name,
                'currency' => $statement->account->currency,
                'dueOn' => $statement->due_on->toDateString(),
                'amountMinor' => (string) $statement->amount_minor,
                'paidAmountMinor' => (string) $statement->paid_amount_minor,
                'remainingMinor' => (string) ($statement->amount_minor - $statement->paid_amount_minor),
                'amount' => MoneyAmount::formatInput($statement->amount_minor - $statement->paid_amount_minor),
            ],
            'accounts' => $request->user()->accounts()
                ->whereIn('type', [AccountType::Bank->value, AccountType::Cash->value])
                ->where('currency', $statement->account->currency)
                ->where('is_active', true)
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
            'today' => today()->toDateString(),
        ]);
    }

    public function store(CreditCardPaymentRequest $request, int $credit_card_statement): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $credit_card_statement, $data): void {
            $statement = $request->user()->creditCardStatements()
                ->with('account')
                ->lockForUpdate()
                ->findOrFail($credit_card_statement);
            Gate::authorize('view', $statement);

            $source = $request->user()->accounts()
                ->whereIn('type', [AccountType::Bank->value, AccountType::Cash->value])
                ->where('is_active', true)
                ->where('currency', $statement->account->currency)
                ->lockForUpdate()
                ->findOrFail((int) $data['account_id']);
            $amountMinor = MoneyAmount::toMinor($data['amount']);
            $remainingMinor = $statement->amount_minor - $statement->paid_amount_minor;

            if ($amountMinor > $remainingMinor) {
                throw ValidationException::withMessages([
                    'amount' => 'Ödeme tutarı kalan ekstre borcunu aşamaz.',
                ]);
            }

            $request->user()->transactions()->create([
                'account_id' => (int) $source->id,
                'destination_account_id' => (int) $statement->account_id,
                'credit_card_statement_id' => (int) $statement->id,
                'type' => TransactionType::Transfer,
                'amount_minor' => $amountMinor,
                'description' => 'Kredi kartı ekstresi ödemesi: '.$statement->account->name,
                'occurred_at' => $data['paid_at'],
            ]);

            $paidAmountMinor = $statement->paid_amount_minor + $amountMinor;
            $statement->update([
                'paid_amount_minor' => $paidAmountMinor,
                'paid_at' => $paidAmountMinor === $statement->amount_minor ? $data['paid_at'] : null,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kart ödemesi transfer olarak kaydedildi.']);

        return to_route('credit-card-statements.index');
    }
}
