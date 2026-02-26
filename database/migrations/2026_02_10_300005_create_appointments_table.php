<?php

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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->dateTime('scheduled_start_at');
            $table->dateTime('scheduled_end_at');
            $table->string('status', 20)->default('scheduled'); // scheduled, in_waiting_room, in_progress, completed, cancelled, no_show
            $table->string('reserve_channel', 50)->nullable(); // whatsapp, manual, web
            $table->text('notes')->nullable();

            // Timestamps reales para auditoría
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('real_start_at')->nullable();
            $table->dateTime('real_end_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
