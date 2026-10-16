<?php

use Database\Seeders\MultipageSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_pages', function (Blueprint $t) {
            $t->id();
            $t->string('page_key')->unique();
            $t->string('slug')->unique();
            $t->string('title');
            $t->text('intro')->nullable();
            $t->longText('body')->nullable();
            $t->json('sections')->nullable();
            $t->boolean('is_published')->default(false);
            $t->string('meta_title')->nullable();
            $t->text('meta_description')->nullable();
            $t->string('og_image')->nullable();
            $t->string('canonical_url')->nullable();
            $t->string('robots')->default('index_follow');
            $t->json('draft_data')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });
        Schema::create('site_navigations', function (Blueprint $t) {
            $t->id();
            $t->string('location')->unique();
            $t->json('items');
            $t->json('draft_data')->nullable();
            $t->timestamps();
        });
        Schema::table('seo_pages', fn (Blueprint $t) => $t->json('draft_data')->nullable());
        Schema::table('clinic_branches', function (Blueprint $t) {
            $t->string('slug')->nullable()->unique();
            $t->text('description')->nullable();
            $t->text('directions')->nullable();
            $t->json('service_ids')->nullable();
            $t->string('meta_title')->nullable();
            $t->text('meta_description')->nullable();
            $t->string('og_image')->nullable();
        });
        Schema::table('articles', function (Blueprint $t) {
            $t->string('author_name')->nullable();
            $t->string('medical_reviewer')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->date('reviewed_at')->nullable();
            $t->json('sources')->nullable();
            $t->json('service_ids')->nullable();
            $t->string('og_image')->nullable();
        });
        Schema::table('videos', fn (Blueprint $t) => $t->timestamp('uploaded_at')->nullable());
        Schema::table('services', fn (Blueprint $t) => $t->string('og_image')->nullable());
        foreach (DB::table('clinic_branches')->get() as $branch) {
            DB::table('clinic_branches')->where('id', $branch->id)->update(['slug' => 'branch-'.$branch->id]);
        }
        // This seeder is idempotent and copies existing content; it never replaces clinic data.
        if (DB::table('page_sections')->exists()) {
            app(MultipageSeeder::class)->run();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_navigations');
        Schema::dropIfExists('site_pages');
        Schema::table('seo_pages', fn (Blueprint $t) => $t->dropColumn('draft_data'));
        Schema::table('clinic_branches', fn (Blueprint $t) => $t->dropColumn(['slug', 'description', 'directions', 'service_ids', 'meta_title', 'meta_description', 'og_image']));
        Schema::table('articles', fn (Blueprint $t) => $t->dropColumn(['author_name', 'medical_reviewer', 'published_at', 'reviewed_at', 'sources', 'service_ids', 'og_image']));
        Schema::table('videos', fn (Blueprint $t) => $t->dropColumn('uploaded_at'));
        Schema::table('services', fn (Blueprint $t) => $t->dropColumn('og_image'));
    }
};
