<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\CategoryController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:client|admin'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/users', function () {
        return User::paginate(15);
    })->middleware('permission:read-users');

    Route::apiResource('categories', CategoryController::class);

    // Logout route
    Route::post('/logout', [AuthController::class, 'logout']);
});

//Client Routes

//Admin Routes


Route::post('register', [AuthController::class, 'register'])
    ->middleware('permission:user-register');
Route::post('login', [AuthController::class, 'login']);
