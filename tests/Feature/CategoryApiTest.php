<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $client;
    protected User $admin;
    protected User $otherClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->otherClient = User::factory()->create();
        $this->otherClient->assignRole('client');
    }

    //
    // AUTHENTICATION TESTS
    //

    public function test_rejects_unauthenticated_category_index_requests(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(401);
    }

    public function test_rejects_unauthenticated_category_creation(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Food'
        ]);

        $response->assertStatus(401);
    }

    public function test_rejects_unauthenticated_category_updates(): void
    {
        $category = Category::factory()->create();

        $response = $this->patchJson("/api/categories/{$category->id}", [
            'name' => 'Updated'
        ]);

        $response->assertStatus(401);
    }

    public function test_rejects_unauthenticated_category_deletion(): void
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(401);
    }

    //
    // INDEX TESTS
    //

    public function test_allows_clients_to_view_their_own_categories_only(): void
    {
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
            ->assertJsonCount(1, 'data');
    }

    public function test_allows_admin_to_view_all_categories(): void
    {
        Sanctum::actingAs($this->admin);

        Category::factory()->count(3)->create([
            'user_id' => $this->client->id
        ]);

        $response = $this->getJson('/api/categories');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total'
            ]);
    }

    //
    // STORE TESTS
    //

    public function test_allows_clients_to_create_their_own_category(): void
    {
        Sanctum::actingAs($this->client);

        $response = $this->postJson('/api/categories', [
            'name' => 'Food'
        ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'name' => 'Food',
                'user_id' => $this->client->id
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Food',
            'user_id' => $this->client->id
        ]);
    }

    public function test_allows_admin_to_create_category_for_any_user(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/categories', [
            'name' => 'Admin Category',
            'user_id' => $this->client->id
        ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'name' => 'Admin Category',
                'user_id' => $this->client->id
            ]);
    }

    public function test_validates_required_name_when_creating_category(): void
    {
        Sanctum::actingAs($this->client);

        $response = $this->postJson('/api/categories', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    //
    // SHOW TESTS
    //

    public function test_allows_clients_to_view_their_own_category(): void
    {
        Sanctum::actingAs($this->client);

        $category = Category::factory()->create([
            'user_id' => $this->client->id
        ]);

        $response = $this->getJson("/api/categories/{$category->id}");

        $response
            ->assertStatus(200)
            ->assertJson([
                'id' => $category->id
            ]);
    }

    public function test_prevents_clients_from_viewing_another_users_category(): void
    {
        Sanctum::actingAs($this->client);

        $category = Category::factory()->create([
            'user_id' => $this->otherClient->id
        ]);

        $response = $this->getJson("/api/categories/{$category->id}");

        $response
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden'
            ]);
    }

    //
    // UPDATE TESTS
    //

    public function test_allows_clients_to_update_their_own_category(): void
    {
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
                'name' => 'Updated Name'
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_prevents_clients_from_updating_another_users_category(): void
    {
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
                'message' => 'Forbidden'
            ]);
    }

    //
    // DELETE TESTS
    //

    public function test_allows_clients_to_delete_their_own_category(): void
    {
        Sanctum::actingAs($this->client);

        $category = Category::factory()->create([
            'user_id' => $this->client->id
        ]);

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Deleted'
            ]);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id
        ]);
    }

    public function test_prevents_clients_from_deleting_another_users_category(): void
    {
        Sanctum::actingAs($this->client);

        $category = Category::factory()->create([
            'user_id' => $this->otherClient->id
        ]);

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(403);
    }

    //
    // USER ROUTE TESTS
    //

    public function test_allows_authenticated_users_to_access_user_endpoint(): void
    {
        Sanctum::actingAs($this->client);

        $response = $this->getJson('/api/user');

        $response
            ->assertStatus(200)
            ->assertJson([
                'id' => $this->client->id
            ]);
    }
}
