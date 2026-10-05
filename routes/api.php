<?php

use App\Http\Controllers\PortfolioTrafficController;
use App\Http\Controllers\ShowcaseTrafficController;
use Illuminate\Support\Facades\Route;

Route::post('/portfolio-traffic', PortfolioTrafficController::class)
    ->middleware('throttle:portfolio-traffic')
    ->name('portfolio-traffic.store');

Route::post('/showcase-traffic', ShowcaseTrafficController::class)
    ->middleware('throttle:showcase-traffic')
    ->name('showcase-traffic.store');
