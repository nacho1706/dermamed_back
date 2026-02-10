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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->after('id')->constrained('roles')->restrictOnDelete();
            $table->string('cuit', 20)->nullable()->unique()->after('password');
            $table->string('specialty', 100)->nullable()->after('cuit');
            $table->boolean('is_active')->default(true)->after('specialty');

            // Remove fields not used in JWT API
            $table->dropColumn(['email_verified_at', 'remember_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'cuit', 'specialty', 'is_active']);

            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
        });
    }
};
