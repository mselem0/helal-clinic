<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['site_pages', 'seo_pages'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->timestamp('public_updated_at')->nullable());
        }
    }

    public function down(): void
    {
        foreach (['site_pages', 'seo_pages'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('public_updated_at'));
        }
    }
};
