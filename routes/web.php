<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\BillPaymentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CreditCardPaymentController;
use App\Http\Controllers\CreditCardStatementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanPaymentController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('accounts', AccountController::class)->except('show');
    Route::resource('bills', BillController::class)->except('show');
    Route::get('bills/{bill}/occurrences/{occurrence}/pay', [BillPaymentController::class, 'create'])
        ->name('bills.occurrences.create');
    Route::post('bills/{bill}/occurrences/{occurrence}/pay', [BillPaymentController::class, 'store'])
        ->name('bills.occurrences.store');
    Route::resource('credit-card-statements', CreditCardStatementController::class)->except('show');
    Route::get('credit-card-statements/{credit_card_statement}/pay', [CreditCardPaymentController::class, 'create'])
        ->name('credit-card-statements.pay.create');
    Route::post('credit-card-statements/{credit_card_statement}/pay', [CreditCardPaymentController::class, 'store'])
        ->name('credit-card-statements.pay.store');
    Route::resource('loans', LoanController::class)->except('show');
    Route::get('loans/{loan}/installments/{installment}/pay', [LoanPaymentController::class, 'create'])
        ->name('loans.installments.pay.create');
    Route::post('loans/{loan}/installments/{installment}/pay', [LoanPaymentController::class, 'store'])
        ->name('loans.installments.pay.store');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('transactions', TransactionController::class)->except('show');
});

require __DIR__.'/settings.php';
