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
        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->dateTime('opening_time');
            $table->dateTime('closing_time')->nullable();

            // Metrics y saldos
            $table->decimal('initial_balance', 10, 2)->default(0);
            $table->decimal('final_balance', 10, 2)->nullable(); // Lo que se cuenta al cerrar
            $table->decimal('system_balance', 10, 2)->nullable(); // Lo esperado según el sistema
            $table->decimal('difference', 10, 2)->nullable(); // final - system

            $table->foreignId('user_id_opened')->constrained('users');
            $table->foreignId('user_id_closed')->nullable()->constrained('users');

            // Estado (open, closed, auditing)
            $table->string('status')->default('open');
            $table->text('justification')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_shifts');
    }
};
