<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seo_global_settings', function (Blueprint $table) {
            $table->string('group')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_global_settings', function (Blueprint $table) {
            $table->enum('group', ['general', 'social', 'sitemap', 'local_business', 'verification'])->change();
        });
    }
};
