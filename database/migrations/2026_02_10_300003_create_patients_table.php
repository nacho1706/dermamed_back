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
            $table->string('cuit', 11)->nullable()->unique();
            $table->string('email', 255)->nullable();
            $table->string('dni')->nullable()->unique();
            $table->string('phone', 50)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('street', 150)->nullable();
            $table->string('street_number', 10)->nullable();
            $table->string('floor', 10)->nullable();
            $table->string('apartment', 10)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->string('country', 100)->default('Argentina');
            $table->string('insurance_provider', 100)->nullable();
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
