<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\CategoryController;
use App\Http\Controllers\Api\v1\ExpenseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok'
    ]);
});

Route::middleware('auth:sanctum')
    ->get('/test', fn (Request $r) => $r->user());

Route::middleware(['auth:sanctum', 'role:admin|client'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/users', function () {
        return User::paginate(15);
    })->middleware('permission:read-users');

    // Category Routes

    Route::apiResource('categories', CategoryController::class)->middleware([
        'index' => 'permission:read-categories|read-own-categories',
        'show' => 'permission:read-categories|read-own-categories',

        'store' => 'permission:create-categories|create-own-categories',

        'update' => 'permission:update-categories|update-own-categories',

        'destroy' => 'permission:delete-categories|delete-own-categories',
    ]);

    // Expenses Routes
    Route::apiResource('expenses', ExpenseController::class)->middleware([
        'index' => 'permission:read-expense|read-own-expense',
        'show' => 'permission:read-expense|read-own-expense',

        'store' => 'permission:create-expense|create-own-expense',

        'update' => 'permission:update-expense|update-own-expense',

        'destroy' => 'permission:delete-expense|delete-own-expense',
    ]);

    // Logout route
    Route::post('/logout', [AuthController::class, 'logout']);
});

//Client Routes

//Admin Routes


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
