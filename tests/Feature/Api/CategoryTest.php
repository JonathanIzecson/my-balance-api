<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_categories(): void
    {
        $this->getJson('/api/categories')
            ->assertUnauthorized()
            ->assertExactJson(['message' => __('auth.unauthenticated')]);
    }

    public function test_user_can_list_own_categories(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Comida', 'user_id' => $user->id]);
        Category::create(['name' => 'Archivada', 'user_id' => $user->id, 'is_active' => false]);
        Category::create(['name' => 'Ajena', 'user_id' => User::factory()->create()->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $category->id)
            ->assertJsonPath('data.0.name', 'Comida')
            ->assertJsonPath('data.0.is_active', true)
            ->assertJsonPath('data.1.name', 'Archivada')
            ->assertJsonPath('data.1.is_active', false);
    }

    public function test_user_can_show_own_category(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Transporte', 'user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson("/api/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', 'Transporte')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_showing_another_users_category_returns_json_not_found(): void
    {
        $category = Category::create([
            'name' => 'Ajena',
            'user_id' => User::factory()->create()->id,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/categories/{$category->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);
    }

    public function test_showing_nonexistent_category_returns_json_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/categories/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);
    }

    public function test_user_can_store_category(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/categories', ['name' => 'Salud'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Salud')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('categories', [
            'name' => 'Salud',
            'user_id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function test_user_can_store_inactive_category(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/categories', [
            'name' => 'Archivada',
            'is_active' => false,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Archivada')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('categories', [
            'name' => 'Archivada',
            'user_id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_store_rejects_missing_name(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/categories', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_rejects_invalid_is_active(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/categories', [
            'name' => 'Comida',
            'is_active' => 'yes',
        ])->assertUnprocessable()->assertJsonValidationErrors(['is_active']);
    }

    public function test_user_can_update_own_category(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Comida', 'user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Supermercado',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Supermercado')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Supermercado',
            'is_active' => false,
            'user_id' => $user->id,
        ]);
    }

    public function test_updating_another_users_category_returns_json_not_found(): void
    {
        $category = Category::create([
            'name' => 'Ajena',
            'user_id' => User::factory()->create()->id,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Tomada',
        ])
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Ajena',
        ]);
    }

    public function test_user_can_delete_own_category(): void
    {
        $user = User::factory()->create();
        $account = Account::create(['name' => 'Efectivo', 'user_id' => $user->id]);
        $category = Category::create(['name' => 'Comida', 'user_id' => $user->id]);
        $transaction = Transaction::create([
            'description' => 'Supermercado',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 25,
            'date' => '2026-10-01',
            'type' => 'expense',
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/categories/{$category->id}")->assertNoContent();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
        $this->assertNotSoftDeleted('transactions', ['id' => $transaction->id]);

        $this->getJson("/api/categories/{$category->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_deleting_another_users_category_returns_json_not_found(): void
    {
        $category = Category::create([
            'name' => 'Ajena',
            'user_id' => User::factory()->create()->id,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/categories/{$category->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_deleting_nonexistent_category_returns_json_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson('/api/categories/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);
    }
}
