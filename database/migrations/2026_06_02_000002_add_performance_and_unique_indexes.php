<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Postgres-specific partial unique indexes + performance indexes for
     * frequently filtered/sorted columns. Raw SQL is used for the partial
     * indexes because Laravel's schema builder cannot express WHERE clauses
     * on indexes.
     */
    public function up(): void
    {
        // ── Partial unique indexes (Postgres) ────────────────────────────
        // Only ONE open cash shift can exist at a time.
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS unique_open_cash_shift ON cash_shifts (status) WHERE status = \'open\'');

        // An appointment can have AT MOST one invoice.
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS unique_invoice_appointment ON invoices (appointment_id) WHERE appointment_id IS NOT NULL AND deleted_at IS NULL');

        // ── Performance indexes ─────────────────────────────────────────
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['doctor_id', 'scheduled_start_at'], 'idx_appointments_doctor_start');
            $table->index(['patient_id', 'scheduled_start_at'], 'idx_appointments_patient_start');
            $table->index('status', 'idx_appointments_status');
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->index('patient_id', 'idx_medical_records_patient');
            $table->index('doctor_id', 'idx_medical_records_doctor');
            $table->index(['patient_id', 'date'], 'idx_medical_records_patient_date');
        });

        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->index('payment_date', 'idx_invoice_payments_date');
            $table->index('cash_shift_id', 'idx_invoice_payments_shift');
            $table->index('payment_method_id', 'idx_invoice_payments_method');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index('patient_id', 'idx_invoices_patient');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            // No timestamps on this table — index just product_id for ledger lookups.
            $table->index('product_id', 'idx_stock_movements_product');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_stock_movements_product');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_patient');
        });

        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropIndex('idx_invoice_payments_date');
            $table->dropIndex('idx_invoice_payments_shift');
            $table->dropIndex('idx_invoice_payments_method');
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropIndex('idx_medical_records_patient');
            $table->dropIndex('idx_medical_records_doctor');
            $table->dropIndex('idx_medical_records_patient_date');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_doctor_start');
            $table->dropIndex('idx_appointments_patient_start');
            $table->dropIndex('idx_appointments_status');
        });

        DB::statement('DROP INDEX IF EXISTS unique_invoice_appointment');
        DB::statement('DROP INDEX IF EXISTS unique_open_cash_shift');
    }
};
