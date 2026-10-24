<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (\App\Support\ReviewedArticleLinks::services() as $slug => [$title, $serviceSlug]) {
                $serviceId = DB::table('services')->where('slug', $serviceSlug)->value('id');
                if (! $serviceId) {
                    continue;
                }

                DB::table('articles')
                    ->where('slug', $slug)
                    ->where('title', $title)
                    ->where(fn ($query) => $query->whereNull('service_ids')->orWhere('service_ids', '[]'))
                    ->update(['service_ids' => json_encode([(int) $serviceId])]);
            }
        });
    }

    public function down(): void
    {
        // Keep these editorial associations on rollback; later edits cannot be
        // distinguished safely from values this migration originally added.
    }
};
