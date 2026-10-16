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
        Schema::create('medical_history_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->enum('type', ['chronic_disease', 'allergy', 'surgery', 'family_history', 'other']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('diagnosed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('severity', ['mild', 'moderate', 'severe'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_history_items');
    }
};
