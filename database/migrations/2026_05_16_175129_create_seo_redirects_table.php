<?php

// database/migrations/2026_05_16_175129_create_seo_redirects_table.php

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
        Schema::create('seo_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source_url');
            $table->string('target_url');
            $table->enum('redirect_type', ['301', '302', '307', '410'])->default('301');
            $table->enum('match_type', ['exact', 'prefix', 'regex'])->default('exact');
            $table->integer('hit_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_redirects');
    }
};
