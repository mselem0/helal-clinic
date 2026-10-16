<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->foreign('visit_record_id')->references('id')->on('visit_records')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->dropForeign(['visit_record_id']);
        });
    }
};
