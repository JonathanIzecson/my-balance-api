<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

trait ValidatesTransactionAccount
{
    protected function transactionAccountRule(): Exists
    {
        $rule = Rule::exists('accounts', 'id')->where('user_id', $this->user()->id);

        if (! $this->keepsCurrentAccount()) {
            $rule->where('is_active', true)->withoutTrashed();
        }

        return $rule;
    }

    private function keepsCurrentAccount(): bool
    {
        $transaction = $this->route('expense') ?? $this->route('income');

        return $transaction instanceof Transaction
            && (int) $this->input('account_id') === (int) $transaction->account_id;
    }
}
