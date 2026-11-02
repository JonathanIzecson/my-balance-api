<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_accounts(): void
    {
        $this->getJson('/api/accounts')
            ->assertUnauthorized()
            ->assertExactJson(['message' => __('auth.unauthenticated')]);
    }

    public function test_user_can_list_own_accounts(): void
    {
        $user = User::factory()->create();
        $account = Account::create(['name' => 'Efectivo', 'user_id' => $user->id]);
        Account::create(['name' => 'Cerrada', 'user_id' => $user->id, 'is_active' => false]);
        Account::create(['name' => 'Ajena', 'user_id' => User::factory()->create()->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/accounts')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $account->id)
            ->assertJsonPath('data.0.name', 'Efectivo')
            ->assertJsonPath('data.0.is_active', true)
            ->assertJsonPath('data.1.name', 'Cerrada')
            ->assertJsonPath('data.1.is_active', false);
    }

    public function test_user_can_show_own_account(): void
    {
        $user = User::factory()->create();
        $account = Account::create(['name' => 'Banco', 'user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson("/api/accounts/{$account->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $account->id)
            ->assertJsonPath('data.name', 'Banco')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_showing_another_users_account_returns_json_not_found(): void
    {
        $account = Account::create([
            'name' => 'Ajena',
            'user_id' => User::factory()->create()->id,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/accounts/{$account->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);
    }

    public function test_showing_nonexistent_account_returns_json_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/accounts/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);
    }

    public function test_user_can_store_account(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/accounts', ['name' => 'Ahorro'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ahorro')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('accounts', [
            'name' => 'Ahorro',
            'user_id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function test_user_can_store_inactive_account(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/accounts', [
            'name' => 'Archivada',
            'is_active' => false,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Archivada')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('accounts', [
            'name' => 'Archivada',
            'user_id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_store_rejects_missing_name(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/accounts', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_rejects_invalid_is_active(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/accounts', [
            'name' => 'Efectivo',
            'is_active' => 'yes',
        ])->assertUnprocessable()->assertJsonValidationErrors(['is_active']);
    }

    public function test_user_can_update_own_account(): void
    {
        $user = User::factory()->create();
        $account = Account::create(['name' => 'Efectivo', 'user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->putJson("/api/accounts/{$account->id}", [
            'name' => 'Tarjeta',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Tarjeta')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Tarjeta',
            'is_active' => false,
            'user_id' => $user->id,
        ]);
    }

    public function test_updating_another_users_account_returns_json_not_found(): void
    {
        $account = Account::create([
            'name' => 'Ajena',
            'user_id' => User::factory()->create()->id,
        ]);
        Sanctum::actingAs(User::factory()->create());

        $this->putJson("/api/accounts/{$account->id}", [
            'name' => 'Tomada',
        ])
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Ajena',
        ]);
    }

    public function test_user_can_delete_own_account(): void
    {
        $user = User::factory()->create();
        $account = Account::create(['name' => 'Efectivo', 'user_id' => $user->id]);
        $transaction = Transaction::create([
            'description' => 'Supermercado',
            'account_id' => $account->id,
            'amount' => 25,
            'date' => '2026-10-01',
            'type' => 'expense',
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/accounts/{$account->id}")->assertNoContent();

        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
        $this->assertNotSoftDeleted('transactions', ['id' => $transaction->id]);

        $this->getJson("/api/accounts/{$account->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);

        $this->getJson('/api/accounts')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson("/api/expenses/{$transaction->id}")
            ->assertOk()
            ->assertJsonPath('data.account.id', $account->id)
            ->assertJsonPath('data.account.name', 'Efectivo');
    }

    public function test_deleting_another_users_account_returns_json_not_found(): void
    {
        $account = Account::create([
            'name' => 'Ajena',
            'user_id' => User::factory()->create()->id,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/accounts/{$account->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);

        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
    }

    public function test_deleting_nonexistent_account_returns_json_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson('/api/accounts/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);
    }
}
