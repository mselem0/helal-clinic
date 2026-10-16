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
        Schema::create('visit_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->date('visit_date');
            $table->text('chief_complaint');
            $table->text('diagnosis')->nullable();
            $table->text('examination_notes')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_records');
    }
};
