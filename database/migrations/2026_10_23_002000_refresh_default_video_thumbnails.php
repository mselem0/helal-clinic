<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $covers = [
            '1122351437148430' => 'gestational-diabetes',
            '1683918787070180' => 'postpartum-care',
            '2129128044360416' => 'uterine-polyps',
            '2013840505981997' => 'postpartum-warning',
        ];

        foreach ($covers as $reel => $cover) {
            // Replace only bundled defaults. Uploaded media still takes precedence.
            DB::table('videos')->whereIn('image_path', [
                "assets/reel-{$reel}.jpg", "assets/reel-{$reel}.webp",
            ])->update(['image_path' => "assets/video-{$cover}.webp"]);
        }
    }

    public function down(): void
    {
        // Retain content choices and the original assets on rollback.
    }
};
