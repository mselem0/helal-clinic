<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_branches', function (Blueprint $table) {
            $table->boolean('booking_schedule_enabled')->default(false);
            $table->json('weekly_schedule')->nullable();
            $table->json('schedule_exceptions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clinic_branches', function (Blueprint $table) {
            $table->dropColumn(['booking_schedule_enabled', 'weekly_schedule', 'schedule_exceptions']);
        });
    }
};
