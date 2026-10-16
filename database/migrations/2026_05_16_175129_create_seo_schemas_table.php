<?php

// database/migrations/2026_05_16_175129_create_seo_schemas_table.php

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
        Schema::create('seo_schemas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_page_id')->constrained('seo_pages')->onDelete('cascade');
            $table->string('schema_type');
            $table->json('schema_data');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_schemas');
    }
};
