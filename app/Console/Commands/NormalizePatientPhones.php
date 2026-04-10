<?php

namespace App\Console\Commands;

use App\Models\Patient;
use Illuminate\Console\Command;

class NormalizePatientPhones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patients:normalize-phones
                            {--dry-run : Show what would be changed without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize patient phone numbers to E.164 format (+549XXXXXXXXXX for Argentina)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('Running in DRY-RUN mode. No changes will be saved.');
        }

        $patients = Patient::whereNotNull('phone')->get();

        $updated = 0;
        $skipped = 0;
        $alreadyValid = 0;

        foreach ($patients as $patient) {
            $original = $patient->phone;
            $normalized = $this->normalizePhone($original);

            if ($normalized === null) {
                $this->line("  [SKIP] ID {$patient->id} ({$patient->first_name} {$patient->last_name}): '{$original}' → could not normalize");
                $skipped++;
                continue;
            }

            if ($normalized === $original) {
                $alreadyValid++;
                continue;
            }

            $this->line("  [UPDATE] ID {$patient->id} ({$patient->first_name} {$patient->last_name}): '{$original}' → '{$normalized}'");

            if (! $isDryRun) {
                $patient->phone = $normalized;
                $patient->saveQuietly(); // avoid triggering observers
            }

            $updated++;
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  Already valid:  {$alreadyValid}");
        $this->info("  Updated:        {$updated}");
        $this->warn("  Skipped:        {$skipped}");

        if ($isDryRun && $updated > 0) {
            $this->newLine();
            $this->warn('DRY-RUN: Run without --dry-run to apply these changes.');
        }

        return self::SUCCESS;
    }

    /**
     * Normalize a phone number to E.164 format.
     * Returns null if the number cannot be reliably normalized.
     */
    private function normalizePhone(string $phone): ?string
    {
        // Already valid E.164 with Argentinian mobile format
        if (preg_match('/^\+549\d{10}$/', $phone)) {
            return $phone;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);

        // Remove single leading zero (local trunk prefix)
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (empty($digits)) {
            return null;
        }

        if (strlen($digits) === 10) {
            // Local 10-digit Argentine number (e.g., 1144445555)
            return '+549' . $digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '54')) {
            // With country code but missing mobile '9' (e.g., 541144445555)
            return '+549' . substr($digits, 2);
        }

        if (strlen($digits) === 13 && str_starts_with($digits, '549')) {
            // Already correct Argentinian mobile in digits only (e.g., 5491144445555)
            return '+' . $digits;
        }

        // Cannot determine the correct format — return null to skip
        return null;
    }
}
