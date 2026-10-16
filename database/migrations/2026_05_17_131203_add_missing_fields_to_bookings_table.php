<?php

// database/migrations/2026_05_17_131203_add_missing_fields_to_bookings_table.php

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
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('status', ['new', 'confirmed', 'in_progress', 'cancelled', 'completed'])->default('new')->change();
            $table->boolean('is_read')->default(false)->after('status');
            $table->time('booking_time')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('status', ['new', 'confirmed', 'cancelled', 'completed'])->default('new')->change();
            $table->dropColumn('is_read');
            $table->time('booking_time')->nullable(false)->change();
        });
    }
};
