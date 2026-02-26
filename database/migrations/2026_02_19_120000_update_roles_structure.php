<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename existing 'admin' role to 'clinic_manager' to preserve user associations
        DB::table('roles')
            ->where('name', 'admin')
            ->update(['name' => 'clinic_manager']);

        // 2. Ensure all 3 required roles exist
        $roles = ['clinic_manager', 'doctor', 'receptionist'];

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
        // Revert 'clinic_manager' to 'admin'
        DB::table('roles')
            ->where('name', 'clinic_manager')
            ->update(['name' => 'admin']);
    }
};
