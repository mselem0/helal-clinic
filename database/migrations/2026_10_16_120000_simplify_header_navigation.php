<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $menu = DB::table('site_navigations')->where('location', 'header')->first();
        if (! $menu) {
            return;
        }
        $labels = ['home' => 'الرئيسية', 'about' => 'عن الدكتور', 'services' => 'الخدمات', 'articles' => 'المقالات', 'branches' => 'الفروع', 'appointment' => 'طلب موعد'];
        $items = collect(json_decode($menu->items, true) ?? [])->filter(fn ($item) => isset($labels[$item['page_key'] ?? '']))->map(fn ($item) => array_replace($item, ['label' => $labels[$item['page_key']]]))->values()->all();
        DB::table('site_navigations')->where('id', $menu->id)->update(['items' => json_encode($items, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Keep the clinic's managed navigation; rolling back must not invent deleted links.
    }
};
