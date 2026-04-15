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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'appointment_id')) {
                $table->foreignId('appointment_id')->nullable()->after('patient_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('invoices', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('appointment_id')->constrained()->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['invoices_appointment_id_foreign']);
            $table->dropColumn('appointment_id');
            $table->dropForeign(['invoices_user_id_foreign']);
            $table->dropColumn('user_id');
        });
    }
};
