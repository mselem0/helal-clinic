<?php

// database/migrations/2026_05_16_175128_create_seo_pages_table.php

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
        Schema::create('seo_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_key')->unique();
            $table->string('page_label');
            $table->string('meta_title', 60)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->json('meta_keywords')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->enum('og_type', ['website', 'article', 'profile'])->nullable();
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();
            $table->enum('twitter_card', ['summary', 'summary_large_image'])->nullable();
            $table->enum('robots', ['index_follow', 'noindex_follow', 'index_nofollow', 'noindex_nofollow'])->default('index_follow');
            $table->string('canonical_url')->nullable();
            $table->json('focus_keywords')->nullable();
            $table->tinyInteger('seo_score')->default(0);
            $table->json('score_breakdown')->nullable();
            $table->tinyInteger('readability_score')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_pages');
    }
};
