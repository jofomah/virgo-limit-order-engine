<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [OrderController::class, 'index']);
Route::post('/', [OrderController::class, 'store']);
Route::post('/{order}/cancel', [OrderController::class, 'cancel']);
