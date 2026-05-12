<?php

use App\Http\Controllers\Api\v1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Logout route
    Route::post('/logout', [AuthController::class, 'logout']);
});


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
