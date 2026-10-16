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
        Schema::table('page_sections', function (Blueprint $table) {
            $table->json('draft_data')->nullable();
            $table->timestamp('published_at')->nullable();
            // Change section_key to string to be more flexible than enum
            $table->string('section_key')->change();
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->json('draft_data')->nullable();
        });

        Schema::create('theme_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('content_snapshot');
            $table->string('summary')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_revisions');

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('draft_data');
        });

        Schema::table('page_sections', function (Blueprint $table) {
            $table->dropColumn(['draft_data', 'published_at']);
        });
    }
};
