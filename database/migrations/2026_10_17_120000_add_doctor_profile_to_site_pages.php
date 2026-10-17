<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_pages', function (Blueprint $table) {
            $table->json('doctor_profile')->nullable();
        });
        // Add only the user-supplied professional profile; preserve existing page content and drafts.
        DB::table('site_pages')->where('page_key', 'about')->whereNull('doctor_profile')->update([
            'doctor_profile' => file_get_contents(database_path('content/doctor-profile.json')),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('site_pages', fn (Blueprint $table) => $table->dropColumn('doctor_profile'));
    }
};
