<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IncomeDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_own_income(): void
    {
        $user = User::factory()->create();
        $income = $this->createIncomeFor($user);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/incomes/{$income->id}")->assertNoContent();

        $this->assertSoftDeleted('transactions', ['id' => $income->id]);
    }

    public function test_deleting_nonexistent_income_returns_json_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson('/api/incomes/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);
    }

    public function test_deleting_another_users_income_returns_json_not_found(): void
    {
        $owner = User::factory()->create();
        $income = $this->createIncomeFor($owner);

        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/incomes/{$income->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.resource_not_found')]);

        $this->assertNotSoftDeleted('transactions', ['id' => $income->id]);
    }

    public function test_unknown_api_route_returns_json_not_found(): void
    {
        $this->getJson('/api/this-route-does-not-exist')
            ->assertNotFound()
            ->assertExactJson(['message' => __('api.route_not_found')]);
    }

    private function createIncomeFor(User $user): Transaction
    {
        $account = Account::create(['name' => 'Efectivo', 'user_id' => $user->id]);

        return Transaction::create([
            'description' => 'Salario',
            'account_id' => $account->id,
            'amount' => 1500.00,
            'date' => '2026-10-01',
            'type' => 'income',
            'user_id' => $user->id,
        ]);
    }
}
