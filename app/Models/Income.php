<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Income extends Transaction
{
    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('income_type', function (Builder $builder) {
            $builder->where('type', 'income');
        });

        static::saving(function (Income $income) {
            $income->type = 'income';
        });
    }
}
