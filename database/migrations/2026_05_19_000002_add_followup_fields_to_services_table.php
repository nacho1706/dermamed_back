<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedInteger('followup_default_days')->nullable()
                ->after('doctor_commission_percentage');
            $table->unsignedInteger('followup_notify_anticipation_days')->nullable()
                ->after('followup_default_days');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['followup_default_days', 'followup_notify_anticipation_days']);
        });
    }
};
