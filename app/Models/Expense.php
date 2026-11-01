<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Expense extends Transaction
{
    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('expense_type', function (Builder $builder) {
            $builder->where('type', 'expense');
        });

        static::saving(function (Expense $expense) {
            $expense->type = 'expense';
        });
    }
}
