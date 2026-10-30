<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_booking_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('remote_request_id')->nullable();
            $table->unsignedSmallInteger('sync_attempts')->default(0);
            $table->timestamp('last_sync_attempt_at')->nullable();
            $table->timestamp('next_sync_attempt_at')->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->string('last_sync_error', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('website_booking_requests', fn (Blueprint $table) => $table->dropColumn(['remote_request_id', 'sync_attempts', 'last_sync_attempt_at', 'next_sync_attempt_at', 'synced_at', 'last_sync_error']));
    }
};
