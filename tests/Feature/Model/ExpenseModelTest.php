<?php

use App\Models\Expense;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('expense belongs to user', function () {
    $user = User::factory()->create();

    $expense = Expense::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($expense->user)->toBeInstanceOf(User::class)
        ->and($expense->user->id)->toBe($user->id);
});

test('expense belongs to category', function () {
    $user = User::factory()->create();

    $category = Category::factory()->create([
        'user_id' => $user->id,
    ]);

    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    expect($expense->category)->toBeInstanceOf(Category::class)
        ->and($expense->category->id)->toBe($category->id);
});
