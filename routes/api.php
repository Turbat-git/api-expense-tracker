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

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/users', function () {
        return User::paginate(15);
    })->middleware('permission:read-users');

    Route::post('/logout', [AuthController::class, 'logout']);
});

// Categories
Route::middleware('auth:sanctum')
    ->get('/categories', [CategoryController::class, 'index'])
    ->middleware('permission:read-categories|read-own-categories');

Route::middleware('auth:sanctum')
    ->get('/categories/{category}', [CategoryController::class, 'show'])
    ->middleware('permission:read-categories|read-own-categories');

Route::middleware('auth:sanctum')
    ->post('/categories', [CategoryController::class, 'store'])
    ->middleware('permission:create-categories|create-own-categories');

Route::middleware('auth:sanctum')
    ->patch('/categories/{category}', [CategoryController::class, 'update'])
    ->middleware('permission:update-categories|update-own-categories');

Route::middleware('auth:sanctum')
    ->delete('/categories/{category}', [CategoryController::class, 'destroy'])
    ->middleware('permission:delete-categories|delete-own-categories');

// Expenses
Route::middleware('auth:sanctum')
    ->get('/expenses', [ExpenseController::class, 'index'])
    ->middleware('permission:read-expense|read-own-expense');

Route::middleware('auth:sanctum')
    ->get('/expenses/{expense}', [ExpenseController::class, 'show'])
    ->middleware('permission:read-expense|read-own-expense');

Route::middleware('auth:sanctum')
    ->post('/expenses', [ExpenseController::class, 'store'])
    ->middleware('permission:create-expense|create-own-expense');

Route::middleware('auth:sanctum')
    ->patch('/expenses/{expense}', [ExpenseController::class, 'update'])
    ->middleware('permission:update-expense|update-own-expense');

Route::middleware('auth:sanctum')
    ->delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])
    ->middleware('permission:delete-expense|delete-own-expense');


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
