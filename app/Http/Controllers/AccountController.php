<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Models\Account;
use App\Support\AccountBalanceSummary;
use App\Support\MoneyAmount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request, AccountBalanceSummary $balanceSummary): Response
    {
        Gate::authorize('viewAny', Account::class);

        return Inertia::render('accounts/Index', [
            'accounts' => $balanceSummary->forUser($request->user()),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Account::class);

        return Inertia::render('accounts/Form', [
            'account' => null,
        ]);
    }

    public function store(AccountRequest $request): RedirectResponse
    {
        Gate::authorize('create', Account::class);

        $data = $request->validated();

        $request->user()->accounts()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'currency' => 'TRY',
            'opening_balance_minor' => MoneyAmount::toMinor($data['opening_balance']),
            'is_active' => true,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hesap oluşturuldu.']);

        return to_route('accounts.index');
    }

    public function edit(Request $request, int $account): Response
    {
        $account = $request->user()->accounts()->findOrFail($account);
        Gate::authorize('update', $account);

        return Inertia::render('accounts/Form', [
            'account' => [
                'id' => (int) $account->id,
                'name' => $account->name,
                'type' => $account->type->value,
                'openingBalance' => MoneyAmount::formatInput((int) $account->opening_balance_minor),
                'isActive' => $account->is_active,
                'canDelete' => ! $account->transactions()->exists()
                    && ! $account->incomingTransfers()->exists()
                    && ! $account->creditCardStatements()->exists(),
            ],
        ]);
    }

    public function update(AccountRequest $request, int $account): RedirectResponse
    {
        $account = $request->user()->accounts()->findOrFail($account);
        Gate::authorize('update', $account);

        $data = $request->validated();

        if ($account->type->value !== $data['type']
            && ($account->transactions()->exists()
                || $account->incomingTransfers()->exists()
                || $account->creditCardStatements()->exists())) {
            throw ValidationException::withMessages([
                'type' => 'Hareket veya ekstre kaydı bulunan hesabın türü değiştirilemez.',
            ]);
        }

        $account->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'opening_balance_minor' => MoneyAmount::toMinor($data['opening_balance']),
            'is_active' => $data['is_active'] ?? false,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hesap güncellendi.']);

        return to_route('accounts.index');
    }

    public function destroy(Request $request, int $account): RedirectResponse
    {
        $account = $request->user()->accounts()->findOrFail($account);
        Gate::authorize('delete', $account);
        $account->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hesap silindi.']);

        return to_route('accounts.index');
    }
}
