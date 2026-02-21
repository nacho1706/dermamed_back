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
            // Drop old generic address column
            $table->dropColumn('address');

            // Add structured address columns
            $table->string('street', 150)->nullable()->after('birth_date');
            $table->string('street_number', 10)->nullable()->after('street');
            $table->string('floor', 10)->nullable()->after('street_number');
            $table->string('apartment', 10)->nullable()->after('floor');
            $table->string('city', 100)->nullable()->after('apartment');
            $table->string('province', 100)->nullable()->after('city');
            $table->string('zip_code', 10)->nullable()->after('province');
            $table->string('country', 100)->default('Argentina')->after('zip_code');
        });

        // Modify CUIT column size separately (keep existing unique constraint)
        Schema::table('patients', function (Blueprint $table) {
            $table->string('cuit', 11)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'street',
                'street_number',
                'floor',
                'apartment',
                'city',
                'province',
                'zip_code',
                'country',
            ]);

            $table->string('address', 255)->nullable()->after('birth_date');
        });

        if (Schema::hasColumn('patients', 'cuit')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('cuit', 20)->nullable()->change();
            });
        }
    }
};
