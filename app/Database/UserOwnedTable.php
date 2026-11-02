<?php

namespace App\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class UserOwnedTable
{
    private function __construct() {}

    public static function create(string $table): void
    {
        Schema::create($table, function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name');
            $blueprint->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });
    }

    public static function drop(string $table): void
    {
        Schema::dropIfExists($table);
    }
}
