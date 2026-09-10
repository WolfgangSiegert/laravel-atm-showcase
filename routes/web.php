<?php

use App\Http\Controllers\AtmSessionController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Middleware\RequireAtmSession;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => to_route('atm.index'))->name('home');
Route::get('/atm', fn () => Inertia::render('Atm/Welcome', ['version' => '0.5.0']))->name('atm.index');
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
