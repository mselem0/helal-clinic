<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clinic_branches', fn (Blueprint $table) => $table->boolean('booking_time_enabled')->default(true));
        if (!Schema::hasColumn('website_booking_requests', 'booking_time')) {
            Schema::table('website_booking_requests', fn (Blueprint $table) => $table->string('booking_time', 5)->nullable());
        }
    }
    public function down(): void
    {
        // Keep original times on rollback; an integration may already own this column.
        Schema::table('clinic_branches', fn (Blueprint $table) => $table->dropColumn('booking_time_enabled'));
    }
};
