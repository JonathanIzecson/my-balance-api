<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Income extends Transaction
{
    protected static function booted(): void
    {
        static::addGlobalScope('income_type', function (Builder $builder) {
            $builder->where('type', 'income')
                ->where('user_id', Auth::id())
                ->with('account');
        });

        static::saving(function (Income $income) {
            $income->type = 'income';
        });
    }
}
