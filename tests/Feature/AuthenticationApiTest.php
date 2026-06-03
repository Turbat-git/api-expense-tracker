<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\PersonalAccessToken;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('allows user to register', function () {

    $response = $this->postJson('/api/register', [
        'given_name' => 'John',
        'family_name' => 'Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response
        ->assertStatus(201)
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'user' => [
                'id',
                'given_name',
                'family_name',
                'email',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'given_name' => 'John',
        'family_name' => 'Doe',
    ]);

    $user = User::where('email', 'john@example.com')->first();

    expect($user->hasRole('client'))->toBeTrue();
});

test('validates required fields when registering', function () {

    $response = $this->postJson('/api/register', []);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'given_name',
            'family_name',
            'email',
            'password',
        ]);
});

test('validates unique email when registering', function () {

    User::factory()->create([
        'email' => 'john@example.com',
    ]);

    $response = $this->postJson('/api/register', [
        'given_name' => 'John',
        'family_name' => 'Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('allows user to login', function () {

    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'john@example.com',
        'password' => 'password123',
        'device_name' => 'iPhone 15',
    ]);

    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'access_token',
            'token_type',
        ]);
});

test('returns error for incorrect password', function () {

    User::factory()->create([
        'email' => 'john@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'john@example.com',
        'password' => 'wrong-password',
    ]);

    $response
        ->assertStatus(401)
        ->assertJson([
            'message' => 'Incorrect Password',
        ]);
});

test('validates required fields when logging in', function () {

    $response = $this->postJson('/api/login', []);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'email',
            'password',
        ]);
});

test('allows authenticated user to logout', function () {

    $user = User::factory()->create();

    $token = $user->createToken('test-token');

    $response = $this->withHeader(
        'Authorization',
        'Bearer '.$token->plainTextToken
    )->postJson('/api/logout');

    $response
        ->assertStatus(200)
        ->assertJson([
            'message' => 'Logout Successful',
        ]);

    expect(PersonalAccessToken::count())->toBe(0);
});

test('prevents unauthenticated user from logging out', function () {

    $response = $this->postJson('/api/logout');

    $response->assertStatus(401);
});
