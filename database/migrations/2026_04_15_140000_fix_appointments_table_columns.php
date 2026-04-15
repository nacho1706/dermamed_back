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
        Schema::table('appointments', function (Blueprint $table) {
            // Renombrar columnas si existen con los nombres antiguos
            if (Schema::hasColumn('appointments', 'start_time') && !Schema::hasColumn('appointments', 'scheduled_start_at')) {
                $table->renameColumn('start_time', 'scheduled_start_at');
            }
            
            if (Schema::hasColumn('appointments', 'end_time') && !Schema::hasColumn('appointments', 'scheduled_end_at')) {
                $table->renameColumn('end_time', 'scheduled_end_at');
            }

            // Agregar columna faltante is_overbook
            if (!Schema::hasColumn('appointments', 'is_overbook')) {
                $table->boolean('is_overbook')->default(false)->after('reserve_channel');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'scheduled_start_at')) {
                $table->renameColumn('scheduled_start_at', 'start_time');
            }
            
            if (Schema::hasColumn('appointments', 'scheduled_end_at')) {
                $table->renameColumn('scheduled_end_at', 'end_time');
            }

            if (Schema::hasColumn('appointments', 'is_overbook')) {
                $table->dropColumn('is_overbook');
            }
        });
    }
};
