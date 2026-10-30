<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('website_booking_requests', function (Blueprint $table) {
            $table->string('visit_type', 30)->nullable();
            $table->string('visit_window', 255)->nullable();
            $table->timestamp('visit_window_acknowledged_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('website_booking_requests', fn (Blueprint $table) => $table->dropColumn(['visit_type', 'visit_window', 'visit_window_acknowledged_at']));
    }
};
