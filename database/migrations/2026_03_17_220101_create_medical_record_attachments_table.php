<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_record_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')
                ->constrained('medical_records')
                ->cascadeOnDelete();
            $table->string('path');          // ruta en disco local (private/)
            $table->string('original_name'); // nombre original del archivo
            $table->string('mime_type');     // image/jpeg, image/png, etc.
            $table->unsignedBigInteger('size'); // bytes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_record_attachments');
    }
};
