<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route('dashboard'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->name('register.store')
        ->middleware('throttle:5,1');

    Route::get('auth/google', [GoogleLoginController::class, 'redirect'])->name('google.redirect');
    Route::get('auth/google/callback', [GoogleLoginController::class, 'callback'])->name('google.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('verify-email/send', [EmailVerificationController::class, 'send'])->name('verification.send');

    Route::post('logout', LogoutController::class)->name('logout');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('cards', [CardController::class, 'index'])->name('cards.index');
    Route::post('cards', [CardController::class, 'store'])->name('cards.store');
    Route::put('cards/{card}', [CardController::class, 'update'])->name('cards.update');
    Route::delete('cards/{card}', [CardController::class, 'destroy'])->name('cards.destroy');
    Route::post('cards/bulk-destroy', [CardController::class, 'bulkDestroy'])->name('cards.bulk-destroy');
    Route::get('/cards/{card}/purchases', [CardController::class, 'purchases'])->name('cards.purchases');

    Route::resource('purchases', PurchaseController::class)->except(['create', 'edit']);
    Route::patch('/purchases/{purchase}/mark-as-paid', [PurchaseController::class, 'markAsPaid'])->name('purchases.mark-as-paid');
    Route::patch('/purchases/{purchase}/unmark-as-paid', [PurchaseController::class, 'unmarkAsPaid'])->name('purchases.unmark-as-paid');
    Route::post('/purchases/reorder', [PurchaseController::class, 'reorder'])->name('purchases.reorder');

    Route::patch('/invoices/{invoice}/mark-as-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-as-paid');
    Route::patch('/invoices/{invoice}/unmark-as-paid', [InvoiceController::class, 'unmarkAsPaid'])->name('invoices.unmark-as-paid');

    Route::get('/incomes', [IncomeController::class, 'index'])->name('incomes.index');
    Route::post('/incomes', [IncomeController::class, 'store'])->name('incomes.store');
    Route::put('/incomes/{income}', [IncomeController::class, 'update'])->name('incomes.update');
    Route::delete('/incomes/{income}', [IncomeController::class, 'destroy'])->name('incomes.destroy');
    Route::patch('/income-months/{incomeMonth}', [IncomeController::class, 'updateMonth'])->name('incomes.update-month');
    Route::post('/incomes/{income}/months/fill', [IncomeController::class, 'fillMonths'])->name('incomes.fill-months');
    Route::post('/incomes/{income}/duplicate', [IncomeController::class, 'duplicate'])->name('incomes.duplicate');
    Route::delete('/income-months/{incomeMonth}', [IncomeController::class, 'deleteMonth'])->name('incomes.delete-month');

    Route::get('profile', [ProfileController::class, 'index'])->name('profile');
    Route::patch('profile/name', [ProfileController::class, 'updateName'])->name('profile.update-name');
    Route::patch('profile/email', [ProfileController::class, 'updateEmail'])->name('profile.update-email');
    Route::patch('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    Route::post('profile/destroy', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('profile/confirm-destroy/{user}', [ProfileController::class, 'confirmDestroy'])->name('profile.confirm-destroy');

    Route::patch('/preferences', [PreferencesController::class, 'update'])->name('preferences.update');
});
