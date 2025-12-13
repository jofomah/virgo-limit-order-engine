<?php

use App\Http\Controllers\Api\ProfileController;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')
    ->prefix('auth')
    ->group(function () {
        require __DIR__ . '/modules/auth.php';
    });

Broadcast::routes(['middleware' => ['auth:sanctum', 'throttle:broadcast']]);

Route::middleware(['throttle:api', 'auth:sanctum'])
    ->group(function () {
        Route::get('/profile', [ProfileController::class, 'me']);
    });

/**
 * Order routes (exchange domain)
 */
Route::middleware(['auth:sanctum', 'throttle:high_frequency'])
    ->prefix('orders')
    ->group(function () {
        require __DIR__ . '/modules/order.php';
    });


