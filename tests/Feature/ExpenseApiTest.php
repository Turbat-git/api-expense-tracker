<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->client = User::factory()->create();
    $this->client->assignRole('client');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->otherClient = User::factory()->create();
    $this->otherClient->assignRole('client');
});

test('returns api health status', function () {
    $response = $this->getJson('/api/health');

    $response
        ->assertStatus(200)
        ->assertJson([
            'status' => 'ok'
        ]);
});

test('allows clients to create expenses', function () {
    Sanctum::actingAs($this->client);

    $response = $this->postJson('/api/expenses', [
        'amount' => 99.50,
        'description' => 'Fuel'
    ]);

    $response
        ->assertStatus(201)
        ->assertJson([
            'amount' => 99.50,
            'description' => 'Fuel',
            'user_id' => $this->client->id
        ]);

    $this->assertDatabaseHas('expenses', [
        'description' => 'Fuel'
    ]);
});

test('allows clients to view their own expenses only', function () {
    Sanctum::actingAs($this->client);

    Expense::factory()->create([
        'user_id' => $this->client->id
    ]);

    Expense::factory()->create([
        'user_id' => $this->otherClient->id
    ]);

    $response = $this->getJson('/api/expenses');

    $response
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('admin accessing expenses index', function () {
    Sanctum::actingAs($this->admin);

    Expense::factory()->create([
        'user_id' => $this->otherClient->id
    ]);

    $response = $this->getJson('/api/expenses');

    $response
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('validates required amount when creating expense', function () {
    Sanctum::actingAs($this->client);

    $response = $this->postJson('/api/expenses', [
        'description' => 'Fuel'
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

test('validates amount must be numeric', function () {
    Sanctum::actingAs($this->client);

    $response = $this->postJson('/api/expenses', [
        'amount' => 'invalid'
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

test('rejects invalid category ids', function () {
    Sanctum::actingAs($this->client);

    $response = $this->postJson('/api/expenses', [
        'amount' => 50,
        'category_id' => 999
    ]);

    $response->assertStatus(422);
});

test('rejects categories belonging to another user', function () {
    Sanctum::actingAs($this->client);

    $category = Category::factory()->create([
        'user_id' => $this->otherClient->id
    ]);

    $response = $this->postJson('/api/expenses', [
        'amount' => 50,
        'category_id' => $category->id
    ]);

    $response
        ->assertStatus(422)
        ->assertJson([
            'message' => 'Invalid category'
        ]);
});

test('allows clients to view their own expense', function () {
    Sanctum::actingAs($this->client);

    $expense = Expense::factory()->create([
        'user_id' => $this->client->id
    ]);

    $response = $this->getJson("/api/expenses/{$expense->id}");

    $response
        ->assertStatus(200)
        ->assertJson([
            'id' => $expense->id
        ]);
});

test('prevents clients from viewing another users expense', function () {
    Sanctum::actingAs($this->client);

    $expense = Expense::factory()->create([
        'user_id' => $this->otherClient->id
    ]);

    $response = $this->getJson("/api/expenses/{$expense->id}");

    $response
        ->assertStatus(403)
        ->assertJson([
            'message' => 'Forbidden'
        ]);
});

test('allows partial expense updates', function () {
    Sanctum::actingAs($this->client);

    $expense = Expense::factory()->create([
        'user_id' => $this->client->id,
        'description' => 'Old'
    ]);

    $response = $this->patchJson("/api/expenses/{$expense->id}", [
        'description' => 'Updated'
    ]);

    $response
        ->assertStatus(200)
        ->assertJson([
            'description' => 'Updated'
        ]);

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'description' => 'Updated'
    ]);
});

test('prevents clients from updating another users expense', function () {
    Sanctum::actingAs($this->client);

    $expense = Expense::factory()->create([
        'user_id' => $this->otherClient->id
    ]);

    $response = $this->patchJson("/api/expenses/{$expense->id}", [
        'description' => 'Hacked'
    ]);

    $response
        ->assertStatus(403)
        ->assertJson([
            'message' => 'Forbidden'
        ]);
});

test('validates update amount is numeric', function () {
    Sanctum::actingAs($this->client);

    $expense = Expense::factory()->create([
        'user_id' => $this->client->id
    ]);

    $response = $this->patchJson("/api/expenses/{$expense->id}", [
        'amount' => 'bad-value'
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

test('allows clients to delete their own expense', function () {
    Sanctum::actingAs($this->client);

    $expense = Expense::factory()->create([
        'user_id' => $this->client->id
    ]);

    $response = $this->deleteJson("/api/expenses/{$expense->id}");

    $response
        ->assertStatus(200)
        ->assertJson([
            'message' => 'Deleted'
        ]);

    $this->assertDatabaseMissing('expenses', [
        'id' => $expense->id
    ]);
});

test('prevents clients from deleting another users expense', function () {
    Sanctum::actingAs($this->client);

    $expense = Expense::factory()->create([
        'user_id' => $this->otherClient->id
    ]);

    $response = $this->deleteJson("/api/expenses/{$expense->id}");

    $response
        ->assertStatus(403)
        ->assertJson([
            'message' => 'Forbidden'
        ]);
});

test('allows authenticated users to access user endpoint', function () {
    Sanctum::actingAs($this->client);

    $response = $this->getJson('/api/user');

    $response
        ->assertStatus(200)
        ->assertJson([
            'id' => $this->client->id
        ]);
});

test('prevents users without permission from viewing users list', function () {
    Sanctum::actingAs($this->client);

    $response = $this->getJson('/api/users');

    $response->assertStatus(403);
});

test('allows users with permission to view users list', function () {
    $this->admin->givePermissionTo('read-users');

    Sanctum::actingAs($this->admin);

    $response = $this->getJson('/api/users');

    $response->assertStatus(200);
});
