<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_booking_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('remote_booking_id')->nullable();
            $table->string('integration_state', 20)->nullable();
            $table->string('payload_hash', 64)->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->string('booking_time', 5)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('website_booking_requests', fn (Blueprint $table) => $table->dropColumn(['remote_booking_id', 'integration_state', 'payload_hash', 'doctor_id', 'booking_time']));
    }
};
