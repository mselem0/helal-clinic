<?php

// database/migrations/2026_05_16_170514_create_about_doctors_table.php

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
        Schema::create('about_doctors', function (Blueprint $table) {
            $table->id();
            $table->string('bio_title');
            $table->longText('bio_text');
            $table->integer('years_experience')->default(0);
            $table->integer('surgeries_count')->default(0);
            $table->integer('patients_count')->default(0);
            $table->string('stats_label_1')->default('سنة خبرة');
            $table->string('stats_label_2')->default('عملية ناجحة');
            $table->string('stats_label_3')->default('مريض راضٍ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_doctors');
    }
};
