<?php

use Illuminate\Support\Facades\Route;

Route::post('/login', function () {
    return response()->json("You have logged in!");
});

Route::post('/logout', function () {
    return response()->json("You have logged out!");
});