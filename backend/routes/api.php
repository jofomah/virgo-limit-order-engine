<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')
    ->prefix('auth')
    ->group(function () {
        require __DIR__ . '/modules/auth.php';
    });


Broadcast::routes(['middleware' => ['auth:sanctum', 'throttle:broadcast']]);

