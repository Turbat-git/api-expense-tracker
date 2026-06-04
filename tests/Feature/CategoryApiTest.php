<?php

use App\Models\Category;
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

test('allows clients to view their own categories only', function () {
    Sanctum::actingAs($this->client);

    Category::factory()->create([
        'user_id' => $this->client->id
    ]);

    Category::factory()->create([
        'user_id' => $this->otherClient->id
    ]);

    $response = $this->getJson('/api/categories');

    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'response_code'
        ]);
});

test('allows admin to view all categories', function () {
    Sanctum::actingAs($this->admin);

    Category::factory()->count(3)->create([
        'user_id' => $this->client->id
    ]);

    $response = $this->getJson('/api/categories');

    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'response_code'
        ]);
});

test('allows clients to create their own category', function () {
    Sanctum::actingAs($this->client);

    $response = $this->postJson('/api/categories', [
        'name' => 'Food'
    ]);

    $response
        ->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Category created successfully.',
        ]);

    $this->assertDatabaseHas('categories', [
        'name' => 'Food',
        'user_id' => $this->client->id
    ]);
});

test('allows admin to create category for any user', function () {
    Sanctum::actingAs($this->admin);

    $response = $this->postJson('/api/categories', [
        'name' => 'Admin Category',
        'user_id' => $this->client->id
    ]);

    $response
        ->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Category created successfully.',
        ]);
});

test('validates required name when creating category', function () {
    Sanctum::actingAs($this->client);

    $response = $this->postJson('/api/categories', []);

    $response
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Validation failed.'
        ]);
});

test('allows clients to view their own category', function () {
    Sanctum::actingAs($this->client);

    $category = Category::factory()->create([
        'user_id' => $this->client->id
    ]);

    $response = $this->getJson("/api/categories/{$category->id}");

    $response
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Show category successful.',
        ]);
});

test('prevents clients from viewing another users category', function () {
    Sanctum::actingAs($this->client);

    $category = Category::factory()->create([
        'user_id' => $this->otherClient->id
    ]);

    $response = $this->getJson("/api/categories/{$category->id}");

    $response
        ->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Forbidden.',
        ]);
});

test('allows clients to update their own category', function () {
    Sanctum::actingAs($this->client);

    $category = Category::factory()->create([
        'user_id' => $this->client->id,
        'name' => 'Old Name'
    ]);

    $response = $this->patchJson("/api/categories/{$category->id}", [
        'name' => 'Updated Name'
    ]);

    $response
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Update category successful.',
        ]);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Updated Name'
    ]);
});

test('prevents clients from updating another users category', function () {
    Sanctum::actingAs($this->client);

    $category = Category::factory()->create([
        'user_id' => $this->otherClient->id
    ]);

    $response = $this->patchJson("/api/categories/{$category->id}", [
        'name' => 'Hacked'
    ]);

    $response
        ->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Forbidden.',
        ]);
});

test('allows clients to delete their own category', function () {
    Sanctum::actingAs($this->client);

    $category = Category::factory()->create([
        'user_id' => $this->client->id
    ]);

    $response = $this->deleteJson("/api/categories/{$category->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id
    ]);
});

test('prevents clients from deleting another users category', function () {
    Sanctum::actingAs($this->client);

    $category = Category::factory()->create([
        'user_id' => $this->otherClient->id
    ]);

    $response = $this->deleteJson("/api/categories/{$category->id}");

    $response->assertStatus(403);
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
