<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_expenses(): void
    {
        $this->getJson('/api/expenses')
            ->assertUnauthorized()
            ->assertExactJson(['message' => __('auth.unauthenticated')]);
    }

    public function test_user_can_list_own_expenses(): void
    {
        $user = User::factory()->create();
        $expense = $this->createExpenseFor($user, ['description' => 'Supermercado', 'amount' => 42.50, 'date' => '2026-10-15']);
        $this->createExpenseFor(User::factory()->create(), ['description' => 'Ajeno']);
        $this->createIncomeFor($user, ['description' => 'Salario']);

        Sanctum::actingAs($user);

        $this->getJson('/api/expenses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $expense->id)
            ->assertJsonPath('data.0.description', 'Supermercado')
            ->assertJsonPath('data.0.amount', '42.50')
            ->assertJsonPath('data.0.date', '15-10-2026')
            ->assertJsonPath('data.0.account.id', $expense->account_id)
            ->assertJsonPath('data.0.account.name', 'Efectivo');
    }

    public function test_user_can_show_own_expense(): void
    {
        $user = User::factory()->create();
        $expense = $this->createExpenseFor($user);

        Sanctum::actingAs($user);

        $this->getJson("/api/expenses/{$expense->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $expense->id)
            ->assertJsonPath('data.description', 'Supermercado')
            ->assertJsonPath('data.amount', '25.00')
            ->assertJsonPath('data.date', '01-10-2026');
    }

    public function test_showing_another_users_expense_returns_json_not_found(): void
    {
        $expense = $this->createExpenseFor(User::factory()->create());

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/expenses/{$expense->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);
    }

    public function test_showing_nonexistent_expense_returns_json_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/expenses/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);
    }

    public function test_user_can_store_expense(): void
    {
        $user = User::factory()->create();
        $account = Account::create(['name' => 'Banco', 'user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/expenses', [
            'description' => 'Renta',
            'account_id' => $account->id,
            'amount' => 800.5,
            'date' => '2026-10-20',
        ])
            ->assertCreated()
            ->assertJsonPath('data.description', 'Renta')
            ->assertJsonPath('data.amount', '800.50')
            ->assertJsonPath('data.date', '20-10-2026')
            ->assertJsonPath('data.account.id', $account->id)
            ->assertJsonPath('data.account.name', 'Banco');

        $this->assertDatabaseHas('transactions', [
            'description' => 'Renta',
            'account_id' => $account->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'amount' => 800.50,
        ]);
    }

    public function test_store_rejects_missing_fields(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/expenses', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['description', 'account_id', 'amount', 'date']);
    }

    public function test_store_rejects_another_users_account(): void
    {
        $owner = User::factory()->create();
        $account = Account::create(['name' => 'Ajena', 'user_id' => $owner->id]);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/expenses', [
            'description' => 'Renta',
            'account_id' => $account->id,
            'amount' => 10,
            'date' => '2026-10-20',
        ])->assertUnprocessable()->assertJsonValidationErrors(['account_id']);

        $this->assertDatabaseMissing('transactions', ['account_id' => $account->id]);
    }

    public function test_store_rejects_deleted_account(): void
    {
        $user = User::factory()->create();
        $account = Account::create(['name' => 'Efectivo', 'user_id' => $user->id]);
        $account->delete();

        Sanctum::actingAs($user);

        $this->postJson('/api/expenses', [
            'description' => 'Renta',
            'account_id' => $account->id,
            'amount' => 10,
            'date' => '2026-10-20',
        ])->assertUnprocessable()->assertJsonValidationErrors(['account_id']);
    }

    public function test_store_rejects_inactive_account(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'name' => 'Cerrada',
            'user_id' => $user->id,
            'is_active' => false,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/expenses', [
            'description' => 'Renta',
            'account_id' => $account->id,
            'amount' => 10,
            'date' => '2026-10-20',
        ])->assertUnprocessable()->assertJsonValidationErrors(['account_id']);
    }

    public function test_store_rejects_amount_below_minimum(): void
    {
        $user = User::factory()->create();
        $account = Account::create(['name' => 'Efectivo', 'user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/expenses', [
            'description' => 'Propina',
            'account_id' => $account->id,
            'amount' => 0,
            'date' => '2026-10-20',
        ])->assertUnprocessable()->assertJsonValidationErrors(['amount']);
    }

    public function test_user_can_update_own_expense(): void
    {
        $user = User::factory()->create();
        $expense = $this->createExpenseFor($user);
        $account = Account::create(['name' => 'Tarjeta', 'user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->putJson("/api/expenses/{$expense->id}", [
            'description' => 'Cena',
            'account_id' => $account->id,
            'amount' => 60,
            'date' => '2026-10-21',
        ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Cena')
            ->assertJsonPath('data.amount', '60.00')
            ->assertJsonPath('data.date', '21-10-2026')
            ->assertJsonPath('data.account.id', $account->id);

        $this->assertDatabaseHas('transactions', [
            'id' => $expense->id,
            'description' => 'Cena',
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 60,
        ]);
    }

    public function test_update_allows_keeping_deleted_account(): void
    {
        $user = User::factory()->create();
        $expense = $this->createExpenseFor($user);
        Account::withoutGlobalScopes()->findOrFail($expense->account_id)->delete();

        Sanctum::actingAs($user);

        $this->putJson("/api/expenses/{$expense->id}", [
            'description' => 'Cena',
            'account_id' => $expense->account_id,
            'amount' => 30,
            'date' => '2026-10-21',
        ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Cena')
            ->assertJsonPath('data.account.id', $expense->account_id);

        $this->assertDatabaseHas('transactions', [
            'id' => $expense->id,
            'description' => 'Cena',
            'account_id' => $expense->account_id,
        ]);
    }

    public function test_update_allows_keeping_inactive_account(): void
    {
        $user = User::factory()->create();
        $expense = $this->createExpenseFor($user);
        Account::withoutGlobalScopes()->findOrFail($expense->account_id)->update(['is_active' => false]);

        Sanctum::actingAs($user);

        $this->putJson("/api/expenses/{$expense->id}", [
            'description' => 'Cena',
            'account_id' => $expense->account_id,
            'amount' => 30,
            'date' => '2026-10-21',
        ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Cena')
            ->assertJsonPath('data.account.id', $expense->account_id);
    }

    public function test_update_rejects_switching_to_deleted_account(): void
    {
        $user = User::factory()->create();
        $expense = $this->createExpenseFor($user);
        $deleted = Account::create(['name' => 'Archivada', 'user_id' => $user->id]);
        $deleted->delete();

        Sanctum::actingAs($user);

        $this->putJson("/api/expenses/{$expense->id}", [
            'description' => 'Cena',
            'account_id' => $deleted->id,
            'amount' => 30,
            'date' => '2026-10-21',
        ])->assertUnprocessable()->assertJsonValidationErrors(['account_id']);

        $this->assertDatabaseHas('transactions', [
            'id' => $expense->id,
            'description' => 'Supermercado',
            'account_id' => $expense->account_id,
        ]);
    }

    public function test_update_rejects_switching_to_inactive_account(): void
    {
        $user = User::factory()->create();
        $expense = $this->createExpenseFor($user);
        $inactive = Account::create([
            'name' => 'Cerrada',
            'user_id' => $user->id,
            'is_active' => false,
        ]);

        Sanctum::actingAs($user);

        $this->putJson("/api/expenses/{$expense->id}", [
            'description' => 'Cena',
            'account_id' => $inactive->id,
            'amount' => 30,
            'date' => '2026-10-21',
        ])->assertUnprocessable()->assertJsonValidationErrors(['account_id']);

        $this->assertDatabaseHas('transactions', [
            'id' => $expense->id,
            'description' => 'Supermercado',
        ]);
    }

    public function test_updating_another_users_expense_returns_json_not_found(): void
    {
        $expense = $this->createExpenseFor(User::factory()->create());
        $user = User::factory()->create();
        $account = Account::create(['name' => 'Efectivo', 'user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->putJson("/api/expenses/{$expense->id}", [
            'description' => 'Cena',
            'account_id' => $account->id,
            'amount' => 60,
            'date' => '2026-10-21',
        ])
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);

        $this->assertDatabaseHas('transactions', [
            'id' => $expense->id,
            'description' => 'Supermercado',
        ]);
    }

    public function test_user_can_delete_own_expense(): void
    {
        $user = User::factory()->create();
        $expense = $this->createExpenseFor($user);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/expenses/{$expense->id}")->assertNoContent();

        $this->assertSoftDeleted('transactions', ['id' => $expense->id]);
    }

    public function test_deleting_another_users_expense_returns_json_not_found(): void
    {
        $expense = $this->createExpenseFor(User::factory()->create());

        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/expenses/{$expense->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);

        $this->assertNotSoftDeleted('transactions', ['id' => $expense->id]);
    }

    public function test_deleting_nonexistent_expense_returns_json_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson('/api/expenses/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);
    }

    public function test_income_is_not_reachable_as_expense(): void
    {
        $user = User::factory()->create();
        $income = $this->createIncomeFor($user);

        Sanctum::actingAs($user);

        $this->getJson("/api/expenses/{$income->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createExpenseFor(User $user, array $overrides = []): Transaction
    {
        $account = Account::create(['name' => 'Efectivo', 'user_id' => $user->id]);

        return Transaction::create([
            'description' => 'Supermercado',
            'account_id' => $account->id,
            'amount' => 25.00,
            'date' => '2026-10-01',
            'type' => 'expense',
            'user_id' => $user->id,
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createIncomeFor(User $user, array $overrides = []): Transaction
    {
        $account = Account::create(['name' => 'Nómina', 'user_id' => $user->id]);

        return Transaction::create([
            'description' => 'Salario',
            'account_id' => $account->id,
            'amount' => 1500.00,
            'date' => '2026-10-01',
            'type' => 'income',
            'user_id' => $user->id,
            ...$overrides,
        ]);
    }
}
