<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\JsonResponse;

class DeployController extends Controller
{
    /**
     * Deploy dashboard
     */
    public function index()
    {
        return view('deploy.index');
    }

    /**
     * Run a shell command with multiple fallbacks
     */
    private function runShell(string $command): string
    {
        // Try Symfony Process first
        if (class_exists(\Symfony\Component\Process\Process::class)) {
            try {
                $process = new \Symfony\Component\Process\Process(
                    explode(' ', $command),
                    base_path()
                );
                $process->setTimeout(300);
                $process->run();
                return $process->getOutput() . $process->getErrorOutput();
            } catch (\Exception $e) {
                // fall through to next method
            }
        }

        // Try exec
        if (function_exists('exec')) {
            try {
                $output = [];
                exec('cd ' . base_path() . ' && ' . $command . ' 2>&1', $output, $code);
                return implode("\n", $output);
            } catch (\Exception $e) {
                // fall through
            }
        }

        // Try shell_exec
        if (function_exists('shell_exec')) {
            try {
                $result = shell_exec('cd ' . base_path() . ' && ' . $command . ' 2>&1');
                return $result ?? '';
            } catch (\Exception $e) {
                // fall through
            }
        }

        // Try proc_open
        if (function_exists('proc_open')) {
            try {
                $desc = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
                $proc = proc_open('cd ' . base_path() . ' && ' . $command, $desc, $pipes);
                if (is_resource($proc)) {
                    $stdout = stream_get_contents($pipes[1]);
                    $stderr = stream_get_contents($pipes[2]);
                    fclose($pipes[1]);
                    fclose($pipes[2]);
                    proc_close($proc);
                    return $stdout . $stderr;
                }
            } catch (\Exception $e) {
                // fall through
            }
        }

        return '❌ Shell commands are disabled on this hosting. exec, shell_exec, proc_open are all blocked.';
    }

    // ─── Git & Composer ─────────────────────────────────────────────

    public function gitPull(): JsonResponse
    {
        try {
            $output = $this->runShell('git pull');
            return response()->json(['success' => true, 'message' => 'Git pull executed.', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function gitStatus(): JsonResponse
    {
        try {
            $status = $this->runShell('git status');
            $branch = trim($this->runShell('git branch --show-current'));
            $log = $this->runShell('git log --oneline -5');
            return response()->json([
                'success' => true,
                'message' => "Branch: {$branch}",
                'output' => "Branch: {$branch}\n\n--- Status ---\n{$status}\n\n--- Last 5 Commits ---\n{$log}"
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function composerInstall(): JsonResponse
    {
        try {
            $output = $this->runShell('composer install --no-dev --optimize-autoloader');
            return response()->json(['success' => true, 'message' => 'Composer install completed.', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─── Database ───────────────────────────────────────────────────

    public function migrate(): JsonResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            return response()->json(['success' => true, 'message' => 'Migrations executed successfully.', 'output' => Artisan::output()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function migrateFresh(): JsonResponse
    {
        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
            return response()->json(['success' => true, 'message' => 'Fresh migration executed.', 'output' => Artisan::output()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function migrateWithSeed(): JsonResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true, '--seed' => true]);
            return response()->json(['success' => true, 'message' => 'Migration + Seeding completed.', 'output' => Artisan::output()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function seed(): JsonResponse
    {
        try {
            Artisan::call('db:seed', ['--force' => true]);
            return response()->json(['success' => true, 'message' => 'Database seeded.', 'output' => Artisan::output()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function migrateRollback(): JsonResponse
    {
        try {
            Artisan::call('migrate:rollback');
            return response()->json(['success' => true, 'message' => 'Rollback completed.', 'output' => Artisan::output()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function migrationStatus(): JsonResponse
    {
        try {
            Artisan::call('migrate:status');
            return response()->json(['success' => true, 'message' => 'Migration status:', 'output' => Artisan::output()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─── Cache ──────────────────────────────────────────────────────

    public function clearCache(): JsonResponse
    {
        $output = '';
        try {
            Artisan::call('cache:clear');
            $output .= "✓ " . trim(Artisan::output()) . "\n";
            Artisan::call('config:clear');
            $output .= "✓ " . trim(Artisan::output()) . "\n";
            Artisan::call('route:clear');
            $output .= "✓ " . trim(Artisan::output()) . "\n";
            Artisan::call('view:clear');
            $output .= "✓ " . trim(Artisan::output()) . "\n";
            return response()->json(['success' => true, 'message' => 'All caches cleared.', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'output' => $output], 500);
        }
    }

    public function optimizeCache(): JsonResponse
    {
        $output = '';
        try {
            Artisan::call('config:cache');
            $output .= "✓ " . trim(Artisan::output()) . "\n";
            Artisan::call('route:cache');
            $output .= "✓ " . trim(Artisan::output()) . "\n";
            Artisan::call('view:cache');
            $output .= "✓ " . trim(Artisan::output()) . "\n";
            return response()->json(['success' => true, 'message' => 'Application optimized.', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'output' => $output], 500);
        }
    }

    // ─── System ─────────────────────────────────────────────────────

    public function storageLink(): JsonResponse
    {
        try {
            Artisan::call('storage:link');
            return response()->json(['success' => true, 'message' => 'Storage link created.', 'output' => Artisan::output()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function keyGenerate(): JsonResponse
    {
        try {
            Artisan::call('key:generate', ['--force' => true]);
            return response()->json(['success' => true, 'message' => 'Application key generated.', 'output' => Artisan::output()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function dbCheck(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();

            // Compatible with Laravel 11+ and older
            $tableCount = 0;
            try {
                $tables = Schema::getTables();
                $tableCount = count($tables);
            } catch (\Exception $e) {
                try {
                    $tables = DB::select('SHOW TABLES');
                    $tableCount = count($tables);
                } catch (\Exception $e2) {
                    $tableCount = '?';
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Connected to: {$dbName} ({$tableCount} tables)",
                'output' => "Database: {$dbName}\nTables: {$tableCount}\nDriver: " . config('database.default') . "\nConnection: ✓ OK"
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()], 500);
        }
    }

    // ─── Full Deploy ────────────────────────────────────────────────

    public function fullDeploy(): JsonResponse
    {
        $output = "=== FULL DEPLOY STARTED ===\n\n";
        $hasError = false;

        // 1. Git Pull
        try {
            $gitOutput = $this->runShell('git pull');
            $output .= "✓ Git Pull:\n{$gitOutput}\n";
        } catch (\Exception $e) {
            $output .= "⚠ Git Pull skipped: {$e->getMessage()}\n";
        }

        // 2. Composer Install
        try {
            $compOutput = $this->runShell('composer install --no-dev --optimize-autoloader');
            $output .= "✓ Composer Install:\n{$compOutput}\n";
        } catch (\Exception $e) {
            $output .= "⚠ Composer skipped: {$e->getMessage()}\n";
        }

        // 3. Clear caches
        try {
            Artisan::call('cache:clear');
            $output .= "✓ Cache cleared\n";
            Artisan::call('config:clear');
            $output .= "✓ Config cleared\n";
            Artisan::call('route:clear');
            $output .= "✓ Route cleared\n";
            Artisan::call('view:clear');
            $output .= "✓ View cleared\n";
        } catch (\Exception $e) {
            $output .= "❌ Cache clear failed: {$e->getMessage()}\n";
            $hasError = true;
        }

        // 4. Run migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output .= "✓ Migrations: " . trim(Artisan::output()) . "\n";
        } catch (\Exception $e) {
            $output .= "❌ Migrate failed: {$e->getMessage()}\n";
            $hasError = true;
        }

        // 5. Storage link
        try {
            Artisan::call('storage:link');
            $output .= "✓ Storage link created\n";
        } catch (\Exception $e) {
            $output .= "⚠ Storage link: {$e->getMessage()}\n";
        }

        // 6. Optimize
        try {
            Artisan::call('config:cache');
            $output .= "✓ Config cached\n";
            Artisan::call('route:cache');
            $output .= "✓ Routes cached\n";
            Artisan::call('view:cache');
            $output .= "✓ Views cached\n";
        } catch (\Exception $e) {
            $output .= "❌ Optimize failed: {$e->getMessage()}\n";
            $hasError = true;
        }

        $output .= "\n=== DEPLOY " . ($hasError ? "COMPLETED WITH WARNINGS" : "SUCCESSFUL") . " ===";

        return response()->json([
            'success' => !$hasError,
            'message' => $hasError ? 'Deploy completed with some warnings.' : 'Full deployment successful!',
            'output' => $output
        ]);
    }
}
