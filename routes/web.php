<?php

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
Route::get('/atm', fn () => Inertia::render('Atm/Welcome', ['version' => '0.7.0']))->name('atm.index');
Route::get('/atm/cards', [AtmSessionController::class, 'create'])->name('atm.cards');
Route::post('/atm/session', [AtmSessionController::class, 'store'])->name('atm.login');
Route::get('/atm/session', [AtmSessionController::class, 'show'])->middleware(RequireAtmSession::class)->name('atm.session');
Route::delete('/atm/session', [AtmSessionController::class, 'destroy'])->name('atm.logout');

Route::post('/atm/deposits', DepositController::class)->middleware(RequireAtmSession::class)->name('atm.deposit');
Route::post('/atm/withdrawals', WithdrawalController::class)->middleware(RequireAtmSession::class)->name('atm.withdrawal');
Route::get('/atm/receipts/{receiptReference}', ReceiptController::class)
    ->middleware(RequireAtmSession::class)
    ->where('receiptReference', 'ATM-[0-9A-HJKMNP-TV-Z]{26}')
    ->name('atm.receipt');

Route::get('/operator/login', [OperatorSessionController::class, 'create'])->name('operator.login');
Route::post('/operator/session', [OperatorSessionController::class, 'store'])->name('operator.session.store');
Route::middleware('operator')->group(function () {
    Route::get('/operator', [OperatorDashboardController::class, 'show'])->name('operator.dashboard');
    Route::patch('/operator/atm/status', [OperatorDashboardController::class, 'updateStatus'])->name('operator.atm.status');
    Route::post('/operator/inventory/{cashInventory}/adjust', [OperatorDashboardController::class, 'adjustInventory'])->name('operator.inventory.adjust');
    Route::delete('/operator/session', [OperatorSessionController::class, 'destroy'])->name('operator.session.destroy');
});
