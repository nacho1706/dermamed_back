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
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('insurance_provider');
            $table->foreignId('health_insurance_id')->nullable()->constrained()->nullOnDelete();
            $table->string('affiliate_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['health_insurance_id']);
            $table->dropColumn(['health_insurance_id', 'affiliate_number']);
            $table->string('insurance_provider', 100)->nullable();
        });
    }
};
