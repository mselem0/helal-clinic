<?php

// database/migrations/2026_05_17_131203_add_trust_stats_to_hero_settings_table.php

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
            $table->integer('years_experience')->default(0);
            $table->integer('surgeries_count')->default(0);
            $table->integer('patients_count')->default(0);
            $table->string('stats_label_1')->nullable();
            $table->string('stats_label_2')->nullable();
            $table->string('stats_label_3')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hero_settings', function (Blueprint $table) {
            $table->dropColumn(['years_experience', 'surgeries_count', 'patients_count', 'stats_label_1', 'stats_label_2', 'stats_label_3']);
        });
    }
};
