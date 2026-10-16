<?php

// database/migrations/2026_05_16_170512_create_hero_settings_table.php

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
        Schema::create('hero_settings', function (Blueprint $table) {
            $table->id();
            $table->string('doctor_name');
            $table->string('doctor_title');
            $table->string('doctor_university');
            $table->string('badge_text');
            $table->string('whatsapp_number');
            $table->string('whatsapp_button_text');
            $table->string('booking_button_text');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hero_settings');
    }
};
