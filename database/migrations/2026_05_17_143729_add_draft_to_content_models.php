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
        Schema::table('hero_settings', function (Blueprint $table) {
            $table->json('draft_data')->nullable();
        });

        Schema::table('about_doctors', function (Blueprint $table) {
            $table->json('draft_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('about_doctors', function (Blueprint $table) {
            $table->dropColumn('draft_data');
        });

        Schema::table('hero_settings', function (Blueprint $table) {
            $table->dropColumn('draft_data');
        });
    }
};
