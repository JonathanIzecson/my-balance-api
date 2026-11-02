<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IncomeUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_allows_keeping_deleted_account(): void
    {
        $user = User::factory()->create();
        $account = Account::create(['name' => 'Nómina', 'user_id' => $user->id]);
        $income = Transaction::create([
            'description' => 'Salario',
            'account_id' => $account->id,
            'amount' => 1500,
            'date' => '2026-10-01',
            'type' => 'income',
            'user_id' => $user->id,
        ]);
        $account->delete();

        Sanctum::actingAs($user);

        $this->putJson("/api/incomes/{$income->id}", [
            'description' => 'Bono',
            'account_id' => $account->id,
            'amount' => 200,
            'date' => '2026-10-21',
        ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Bono')
            ->assertJsonPath('data.account.id', $account->id);
    }

    public function test_update_rejects_switching_to_inactive_account(): void
    {
        $user = User::factory()->create();
        $account = Account::create(['name' => 'Nómina', 'user_id' => $user->id]);
        $inactive = Account::create([
            'name' => 'Cerrada',
            'user_id' => $user->id,
            'is_active' => false,
        ]);
        $income = Transaction::create([
            'description' => 'Salario',
            'account_id' => $account->id,
            'amount' => 1500,
            'date' => '2026-10-01',
            'type' => 'income',
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $this->putJson("/api/incomes/{$income->id}", [
            'description' => 'Bono',
            'account_id' => $inactive->id,
            'amount' => 200,
            'date' => '2026-10-21',
        ])->assertUnprocessable()->assertJsonValidationErrors(['account_id']);

        $this->assertDatabaseHas('transactions', [
            'id' => $income->id,
            'description' => 'Salario',
            'account_id' => $account->id,
        ]);
    }
}
