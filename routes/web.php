<?php

use App\Http\Controllers\AdminAccountController;
use App\Http\Controllers\AdminCardController;
use App\Http\Controllers\AtmSessionController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\OperatorDashboardController;
use App\Http\Controllers\OperatorSessionController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Middleware\RequireAtmSession;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => to_route('atm.index'))->name('home');
Route::get('/atm', fn () => Inertia::render('Atm/Welcome', ['version' => '1.4.0']))->name('atm.index');
Route::get('/atm/cards', [AtmSessionController::class, 'create'])->name('atm.cards');
Route::post('/atm/session', [AtmSessionController::class, 'store'])->name('atm.login');
Route::get('/atm/session', [AtmSessionController::class, 'show'])->middleware(RequireAtmSession::class)->name('atm.session');
Route::delete('/atm/session', [AtmSessionController::class, 'destroy'])->name('atm.logout');

Route::post('/atm/deposits', DepositController::class)->middleware([RequireAtmSession::class, 'throttle:demo-bookings'])->name('atm.deposit');
Route::post('/atm/withdrawals', WithdrawalController::class)->middleware([RequireAtmSession::class, 'throttle:demo-bookings'])->name('atm.withdrawal');
Route::get('/atm/receipts/{receiptReference}', ReceiptController::class)
    ->middleware(RequireAtmSession::class)
    ->where('receiptReference', 'ATM-[0-9A-HJKMNP-TV-Z]{26}')
    ->name('atm.receipt');

Route::get('/operator/login', fn () => to_route('operator.login'));
Route::post('/operator/session', [OperatorSessionController::class, 'store']);
Route::get('/admin/login', [OperatorSessionController::class, 'create'])->name('operator.login');
Route::post('/admin/session', [OperatorSessionController::class, 'store'])->name('operator.session.store');
Route::middleware('operator')->group(function () {
    Route::get('/admin', [OperatorDashboardController::class, 'show'])->name('operator.dashboard');
    Route::get('/operator', [OperatorDashboardController::class, 'show']);
    Route::patch('/admin/atm/status', [OperatorDashboardController::class, 'updateStatus'])->name('operator.atm.status');
    Route::post('/admin/inventory/{cashInventory}/adjust', [OperatorDashboardController::class, 'adjustInventory'])->name('operator.inventory.adjust');
    Route::delete('/admin/session', [OperatorSessionController::class, 'destroy'])->name('operator.session.destroy');
    Route::post('/admin/accounts', [AdminAccountController::class, 'store'])->name('admin.accounts.store');
    Route::patch('/admin/accounts/{account}/status', [AdminAccountController::class, 'updateStatus'])->name('admin.accounts.status');
    Route::post('/admin/accounts/{account}/cards', [AdminCardController::class, 'store'])->name('admin.cards.store');
    Route::patch('/admin/cards/{card}/status', [AdminCardController::class, 'updateStatus'])->name('admin.cards.status');
    Route::post('/admin/cards/{card}/reset-lock', [AdminCardController::class, 'resetLock'])->name('admin.cards.reset-lock');
    Route::patch('/operator/atm/status', [OperatorDashboardController::class, 'updateStatus']);
    Route::post('/operator/inventory/{cashInventory}/adjust', [OperatorDashboardController::class, 'adjustInventory']);
    Route::delete('/operator/session', [OperatorSessionController::class, 'destroy']);
});
