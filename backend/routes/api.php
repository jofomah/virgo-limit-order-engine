<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->group(function () {
        require __DIR__ . '/modules/auth.php';
    });
