<?php

// database/migrations/2026_05_16_170514_create_bookings_table.php

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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('patient_name');
            $table->string('patient_phone');
            $table->date('booking_date');
            $table->time('booking_time');
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');
            $table->text('patient_condition')->nullable();
            $table->enum('status', ['new', 'confirmed', 'cancelled', 'completed'])->default('new');
            $table->string('source')->nullable();
            $table->string('medium')->nullable();
            $table->string('campaign')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
