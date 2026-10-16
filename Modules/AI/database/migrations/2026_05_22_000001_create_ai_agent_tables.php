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
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->enum('context_type', ['general', 'patient', 'report'])->default('general');
            $table->unsignedBigInteger('context_id')->nullable();
            $table->integer('total_tokens')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->text('content');
            $table->json('sources')->nullable();
            $table->json('tools_used')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->integer('response_time_ms')->default(0);
            $table->timestamps();
        });

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['medical_protocol', 'clinic_policy', 'drug_info', 'procedure'])->default('medical_protocol');
            $table->string('source_name');
            $table->text('content');
            $table->text('content_ar')->nullable();
            $table->json('embedding')->nullable(); // Vector storage
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
