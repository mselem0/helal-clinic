<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_booking_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_key')->nullable()->unique();
            $table->string('patient_name', 70);
            $table->string('patient_phone', 20)->index();
            $table->date('booking_date')->index();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('clinic_branch_id')->nullable()->index();
            $table->string('service_name');
            $table->string('branch_name');
            $table->string('source_host', 100);
            $table->string('status', 20)->default('new')->index();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_booking_requests');
    }
};
