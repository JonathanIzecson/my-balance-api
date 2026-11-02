<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

abstract class UserOwnedModel extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'user_id', 'is_active'];

    protected $attributes = [
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('current_user', function (Builder $builder) {
            $builder->where('user_id', Auth::id());
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
