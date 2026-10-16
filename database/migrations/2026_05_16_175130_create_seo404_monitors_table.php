<?php

// database/migrations/2026_05_16_175130_create_seo404_monitors_table.php

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
        Schema::create('seo404_monitors', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('referer_url')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->integer('hit_count')->default(1);
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('seo_redirect_id')->nullable()->constrained('seo_redirects')->onDelete('set null');
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo404_monitors');
    }
};
