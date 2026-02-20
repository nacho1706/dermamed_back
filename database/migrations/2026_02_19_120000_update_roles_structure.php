<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename existing 'admin' role to 'system_admin' to preserve user associations
        DB::table('roles')
            ->where('name', 'admin')
            ->update(['name' => 'system_admin']);

        // 2. Ensure all 4 required roles exist
        $roles = ['system_admin', 'clinic_manager', 'doctor', 'receptionist'];

        foreach ($roles as $roleName) {
            if (!DB::table('roles')->where('name', $roleName)->exists()) {
                DB::table('roles')->insert([
                    'name' => $roleName
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 'system_admin' to 'admin'
        DB::table('roles')
            ->where('name', 'system_admin')
            ->update(['name' => 'admin']);
    }
};
