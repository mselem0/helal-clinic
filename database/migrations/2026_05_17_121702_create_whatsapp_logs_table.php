<?php

// database/migrations/2026_05_17_121702_create_whatsapp_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('patient_name');
            $table->string('patient_phone');
            $table->text('message_content');
            $table->enum('source', ['booking_form', 'contact_button', 'chat_widget', 'manual']);
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->foreignId('template_id')->nullable()->constrained('whatsapp_templates')->onDelete('set null');
            $table->enum('status', ['new', 'contacted', 'completed', 'archived'])->default('new');
            $table->text('admin_note')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
