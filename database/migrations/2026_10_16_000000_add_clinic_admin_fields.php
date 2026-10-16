<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('phone')->nullable()->unique());
        }
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('role')->default('admin'));
        }
    }

    public function down(): void
    { /* Shared columns are retained when optional patient modules are installed. */
    }
};
