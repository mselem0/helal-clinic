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
        Schema::create('ai_embeddings', function (Blueprint $table) {
            $table->id();
            $table->morphs('embeddable');
            $table->string('content_hash')->nullable();
            $table->json('vector')->nullable();
            $table->text('text_representation')->nullable();
            $table->enum('status', ['pending', 'synced', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_embeddings');
    }
};
