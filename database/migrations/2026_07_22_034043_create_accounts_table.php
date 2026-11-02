<?php

use App\Database\UserOwnedTable;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        UserOwnedTable::create('accounts');
    }

    public function down(): void
    {
        UserOwnedTable::drop('accounts');
    }
};
