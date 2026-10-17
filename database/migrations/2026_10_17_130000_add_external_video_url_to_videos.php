<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('external_video_url', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('videos', fn (Blueprint $table) => $table->dropColumn('external_video_url'));
    }
};
