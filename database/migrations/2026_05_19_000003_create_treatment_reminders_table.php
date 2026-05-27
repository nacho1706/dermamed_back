<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('due_at');
            $table->date('notify_from_at');

            $table->string('status', 20)->default('pending'); // pending, contacted, dismissed, fulfilled

            $table->text('dismissed_reason')->nullable();
            $table->foreignId('dismissed_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->dateTime('contacted_at')->nullable();
            $table->string('contacted_via', 20)->nullable(); // whatsapp, email, phone, in_person, other
            $table->foreignId('contacted_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'notify_from_at']);
            $table->index('patient_id');
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_reminders');
    }
};
