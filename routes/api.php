<?php

use App\Http\Controllers\PortfolioTrafficController;
use Illuminate\Support\Facades\Route;

Route::post('/portfolio-traffic', PortfolioTrafficController::class)
    ->middleware('throttle:portfolio-traffic')
    ->name('portfolio-traffic.store');
