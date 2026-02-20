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
            $table->timestamp('check_in_at')->nullable()->after('status');
            $table->timestamp('real_start_at')->nullable()->after('check_in_at');
            $table->timestamp('real_end_at')->nullable()->after('real_start_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['check_in_at', 'real_start_at', 'real_end_at']);
        });
    }
};
