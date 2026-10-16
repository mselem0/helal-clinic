<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->string('image_path')->nullable();
            $table->string('card_title')->nullable();
        });
        Schema::table('videos', function (Blueprint $table): void {
            $table->string('image_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('reel')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
        });
        Schema::table('articles', function (Blueprint $table): void {
            $table->string('image_path')->nullable();
            $table->string('category')->nullable();
        });
        Schema::table('testimonials', function (Blueprint $table): void {
            $table->text('source_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn(['image_path', 'card_title']));
        Schema::table('videos', fn (Blueprint $table) => $table->dropColumn(['image_path', 'video_path', 'reel', 'category', 'description']));
        Schema::table('articles', fn (Blueprint $table) => $table->dropColumn(['image_path', 'category']));
        Schema::table('testimonials', fn (Blueprint $table) => $table->dropColumn('source_url'));
    }
};
