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
        Schema::create('print_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->boolean('is_default')->default(false);

            // Header customization
            $table->boolean('hide_header')->default(false);
            $table->text('header_left_ar')->nullable(); // Right side in RTL (Arabic)
            $table->text('header_right_en')->nullable(); // Left side in RTL (English)
            $table->string('logo_path')->nullable();

            // Footer customization
            $table->boolean('hide_footer')->default(false);
            $table->text('footer_text_ar')->nullable();
            $table->text('footer_text_en')->nullable();

            // Print layouts & margins (in millimeters)
            $table->integer('margin_top')->default(0);
            $table->integer('margin_bottom')->default(0);
            $table->integer('margin_left')->default(0);
            $table->integer('margin_right')->default(0);

            // Styling & Design properties
            $table->string('primary_color')->default('#002855');
            $table->string('secondary_color')->default('#00B4D8');
            $table->string('font_family')->default('Almarai');

            // Visual toggles
            $table->boolean('show_signature')->default(true);
            $table->boolean('show_patient_box')->default(true);
            $table->boolean('show_watermark')->default(false);
            $table->string('watermark_text')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_templates');
    }
};
