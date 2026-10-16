<?php

// database/migrations/2026_05_16_170514_create_doctor_credentials_table.php

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
        Schema::create('doctor_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('about_doctor_id')->constrained('about_doctors')->onDelete('cascade');
            $table->enum('type', ['degree', 'certification', 'membership', 'award']);
            $table->string('title');
            $table->string('institution');
            $table->string('year')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_credentials');
    }
};
