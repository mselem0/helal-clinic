<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_booking_sheet_changes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('website_booking_request_id')->index();
            $t->string('field');
            $t->text('base_value')->nullable();
            $t->text('local_value')->nullable();
            $t->text('sheet_value')->nullable();
            $t->text('selected_value')->nullable();
            $t->string('source');
            $t->json('metadata')->nullable();
            $t->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_booking_sheet_changes');
    }
};
