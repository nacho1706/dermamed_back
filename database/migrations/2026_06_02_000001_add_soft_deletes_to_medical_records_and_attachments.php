<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add SoftDeletes to medical history tables and replace cascadeOnDelete
     * from patient_id / doctor_id with restrictOnDelete so a patient or doctor
     * row cannot wipe medical history. Retention of medical records is a legal
     * requirement and physical deletion must not happen by accident.
     */
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->softDeletes();
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['doctor_id']);
            $table->foreign('patient_id')->references('id')->on('patients')->restrictOnDelete();
            $table->foreign('doctor_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('medical_record_attachments', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('medical_record_attachments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['doctor_id']);
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dropSoftDeletes();
        });
    }
};
