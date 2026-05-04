<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * One-shot seeder that imports the legacy Payroll Pre-processor xlsx.
 *
 * Brenda 2026-05-04: "can you import this data for me?" — wraps the
 * `timesheets:import-payroll` artisan command with the bundled xlsx
 * (database/imports/payroll.xlsx) so the operator can run a single
 * command on the production server:
 *
 *     php artisan db:seed --class=PayrollImportSeeder
 *
 * Defaults to LIVE import. Pass `--pretend` to Artisan to switch to
 * dry-run via the SEEDER_DRY_RUN env knob, or just call the command
 * directly with `--dry-run` if a preview is preferred.
 *
 * Idempotent enough: rows carry a marker note "Imported from Payroll
 * Pre-processor xlsx" so they can be selectively rolled back without
 * touching anything else.
 */
class PayrollImportSeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('imports/payroll.xlsx');

        if (! file_exists($file)) {
            $this->command->error("Bundled xlsx not found at {$file}");
            $this->command->line('Make sure database/imports/payroll.xlsx is present (committed in repo).');
            return;
        }

        $dryRun = (bool) env('SEEDER_DRY_RUN', false);

        $this->command->info(
            ($dryRun ? '[DRY RUN] ' : '') .
            'Running payroll xlsx import — file: ' . $file
        );

        $exit = Artisan::call('timesheets:import-payroll', array_filter([
            'file'      => $file,
            '--dry-run' => $dryRun ?: null,
        ]));

        // Stream the command's output through the seeder console
        $this->command->getOutput()->write(Artisan::output());

        if ($exit !== 0) {
            $this->command->error('Import command exited with code ' . $exit);
        }
    }
}
