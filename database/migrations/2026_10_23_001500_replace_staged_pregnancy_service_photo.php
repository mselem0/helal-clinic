<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only replace the supplied default; retain clinic-uploaded and custom images.
        DB::table('services')->whereIn('image_path', [
            'assets/portrait-ultrasound.webp', 'assets/portrait-ultrasound.jpg',
        ])->update(['image_path' => 'assets/service-pregnancy.webp']);
    }

    public function down(): void
    {
        // Content choices are not reverted automatically; the original asset is retained.
    }
};
