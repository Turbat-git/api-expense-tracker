<?php

use App\Models\Category;
use App\Models\User;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('category belongs to user', function () {
    $user = User::factory()->create();

    $category = Category::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($category->user)->toBeInstanceOf(User::class)
        ->and($category->user->id)->toBe($user->id);
});

test('category has many expenses', function () {
    $user = User::factory()->create();

    $category = Category::factory()->create([
        'user_id' => $user->id,
    ]);

    Expense::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    expect($category->expenses)->toHaveCount(1);
});
