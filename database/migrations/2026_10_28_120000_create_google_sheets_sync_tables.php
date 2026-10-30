<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_sheets_connections', function (Blueprint $t) {
            $t->id();
            $t->string('spreadsheet_id')->nullable();
            $t->string('spreadsheet_title')->nullable();
            $t->uuid('installation_key');
            $t->string('worksheet_title')->nullable();
            $t->unsignedInteger('worksheet_id')->nullable();
            $t->timestamp('connected_at')->nullable();
            $t->timestamp('retry_after')->nullable();
            $t->timestamp('last_synced_at')->nullable();
            $t->text('last_error')->nullable();
            $t->timestamps();
        });
        Schema::create('website_booking_sheet_syncs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('website_booking_request_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('status')->default('pending')->index();
            $t->json('snapshot')->nullable();
            $t->json('conflicts')->nullable();
            $t->text('last_error')->nullable();
            $t->timestamp('last_synced_at')->nullable();
            $t->timestamp('retry_after')->nullable();
            $t->timestamps();
        });
        DB::table('website_booking_requests')->whereNull('request_key')->orderBy('id')->eachById(function ($r) {
            DB::table('website_booking_requests')->where('id', $r->id)->update(['request_key' => (string) Str::uuid()]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_booking_sheet_syncs');
        Schema::dropIfExists('google_sheets_connections');
    }
};
