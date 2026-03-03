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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('dni')->unique();
            $table->string('cuit')->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();

            // ── Address ─────────────────────────────────────────────────
            $table->string('street')->nullable();
            $table->string('street_number')->nullable();
            $table->string('floor')->nullable();
            $table->string('apartment')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->default('Argentina');

            // ── Health Insurance ─────────────────────────────────────────
            $table->foreignId('health_insurance_id')->nullable()->constrained()->nullOnDelete();
            $table->string('affiliate_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
