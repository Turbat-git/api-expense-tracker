<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user has correct fillable attributes', function () {
    $user = new User();

    expect($user->getFillable())->toBe([
        'given_name',
        'family_name',
        'email',
        'password',
    ]);
});

test('user hides sensitive attributes', function () {
    $user = new User();

    expect($user->getHidden())->toContain('password')
        ->and($user->getHidden())->toContain('remember_token');
});

test('user has many categories through relation', function () {
    $user = User::factory()->create();

    $category = $user->categories()->create([
        'name' => 'Food',
    ]);

    expect($user->categories)->toHaveCount(1);
    expect($category->user_id)->toBe($user->id);
});

test('user has many expenses through relation', function () {
    $user = User::factory()->create();

    $expense = $user->expenses()->create([
        'amount' => 50,
        'description' => 'Test',
    ]);

    expect($user->expenses)->toHaveCount(1);
    expect($expense->user_id)->toBe($user->id);
});
