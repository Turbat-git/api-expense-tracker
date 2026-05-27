<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExpenseApiTest extends TestCase
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
    // HEALTH CHECK
    //

    public function test_returns_api_health_status(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'ok'
            ]);
    }

    //
    // AUTHENTICATION TESTS
    //

    public function test_rejects_unauthenticated_expense_index_requests(): void
    {
        $response = $this->getJson('/api/expenses');

        $response->assertStatus(401);
    }

    public function test_rejects_unauthenticated_expense_creation(): void
    {
        $response = $this->postJson('/api/expenses', [
            'amount' => 100
        ]);

        $response->assertStatus(401);
    }

    public function test_rejects_unauthenticated_expense_updates(): void
    {
        $expense = Expense::factory()->create();

        $response = $this->patchJson("/api/expenses/{$expense->id}", [
            'amount' => 50
        ]);

        $response->assertStatus(401);
    }

    public function test_rejects_unauthenticated_expense_deletion(): void
    {
        $expense = Expense::factory()->create();

        $response = $this->deleteJson("/api/expenses/{$expense->id}");

        $response->assertStatus(401);
    }

    //
    // INDEX TESTS
    //
    public function test_allows_clients_to_view_their_own_expenses_only(): void
    {
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
    }

    public function test_prevents_admin_from_accessing_expenses_index(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/expenses');

        $response
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized role'
            ]);
    }

    //
    // STORE TESTS
    //
    public function test_allows_clients_to_create_expenses(): void
    {
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
    }

    public function test_validates_required_amount_when_creating_expense(): void
    {
        Sanctum::actingAs($this->client);

        $response = $this->postJson('/api/expenses', [
            'description' => 'Fuel'
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_validates_amount_must_be_numeric(): void
    {
        Sanctum::actingAs($this->client);

        $response = $this->postJson('/api/expenses', [
            'amount' => 'invalid'
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_rejects_invalid_category_ids(): void
    {
        Sanctum::actingAs($this->client);

        $response = $this->postJson('/api/expenses', [
            'amount' => 50,
            'category_id' => 999
        ]);

        $response->assertStatus(422);
    }

    public function test_rejects_categories_belonging_to_another_user(): void
    {
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
    }

    //
    // SHOW TESTS
    //

    public function test_allows_clients_to_view_their_own_expense(): void
    {
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
    }

    public function test_prevents_clients_from_viewing_another_users_expense(): void
    {
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
    }

    //
    // PATCH UPDATE TESTS
    //

    public function test_allows_partial_expense_updates(): void
    {
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
    }

    public function test_prevents_clients_from_updating_another_users_expense(): void
    {
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
    }

    public function test_validates_update_amount_is_numeric(): void
    {
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
    }

    //
    // DELETE TESTS
    //

    public function test_allows_clients_to_delete_their_own_expense(): void
    {
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
    }

    public function test_prevents_clients_from_deleting_another_users_expense(): void
    {
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

    //
    // USERS PERMISSION ROUTE TESTS
    //

    public function test_prevents_users_without_permission_from_viewing_users_list(): void
    {
        Sanctum::actingAs($this->client);

        $response = $this->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_allows_users_with_permission_to_view_users_list(): void
    {
        $this->admin->givePermissionTo('read-users');

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
    }
}
