<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeployController extends Controller
{
    /**
     * Deploy dashboard - shows all available commands
     */
    public function index()
    {
        return view('deploy.index');
    }

    /**
     * Run migrations
     */
    public function migrate()
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            return response()->json(['success' => true, 'message' => 'Migrations executed successfully.', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Run fresh migration (drops all tables and re-migrates)
     */
    public function migrateFresh()
    {
        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
            $output = Artisan::output();
            return response()->json(['success' => true, 'message' => 'Fresh migration executed successfully.', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Run migrations with seed
     */
    public function migrateWithSeed()
    {
        try {
            Artisan::call('migrate', ['--force' => true, '--seed' => true]);
            $output = Artisan::output();
            return response()->json(['success' => true, 'message' => 'Migration with seeding completed.', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Run seeders
     */
    public function seed()
    {
        try {
            Artisan::call('db:seed', ['--force' => true]);
            $output = Artisan::output();
            return response()->json(['success' => true, 'message' => 'Database seeded successfully.', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Rollback last migration
     */
    public function migrateRollback()
    {
        try {
            Artisan::call('migrate:rollback', ['--force' => true]);
            $output = Artisan::output();
            return response()->json(['success' => true, 'message' => 'Rollback completed.', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Clear all caches
     */
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            $output = Artisan::output();
            Artisan::call('config:clear');
            $output .= Artisan::output();
            Artisan::call('route:clear');
            $output .= Artisan::output();
            Artisan::call('view:clear');
            $output .= Artisan::output();
            return response()->json(['success' => true, 'message' => 'All caches cleared.', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cache config/routes/views for production
     */
    public function optimizeCache()
    {
        try {
            Artisan::call('config:cache');
            $output = Artisan::output();
            Artisan::call('route:cache');
            $output .= Artisan::output();
            Artisan::call('view:cache');
            $output .= Artisan::output();
            return response()->json(['success' => true, 'message' => 'Application optimized.', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create storage symlink
     */
    public function storageLink()
    {
        try {
            Artisan::call('storage:link');
            $output = Artisan::output();
            return response()->json(['success' => true, 'message' => 'Storage link created.', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate application key
     */
    public function keyGenerate()
    {
        try {
            Artisan::call('key:generate', ['--force' => true]);
            $output = Artisan::output();
            return response()->json(['success' => true, 'message' => 'Application key generated.', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show migration status
     */
    public function migrationStatus()
    {
        try {
            Artisan::call('migrate:status');
            $output = Artisan::output();
            return response()->json(['success' => true, 'message' => 'Migration status:', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Check database connection
     */
    public function dbCheck()
    {
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            $tables = Schema::getAllTables();
            $tableCount = count($tables);
            return response()->json([
                'success' => true,
                'message' => "Connected to database: {$dbName} ({$tableCount} tables)",
                'output' => "Database: {$dbName}\nTables: {$tableCount}\nConnection: OK"
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Full deploy: clear cache, migrate, seed, optimize
     */
    public function fullDeploy()
    {
        $output = '';
        try {
            // Clear caches
            Artisan::call('cache:clear');
            $output .= "✓ Cache cleared\n";
            Artisan::call('config:clear');
            $output .= "✓ Config cleared\n";
            Artisan::call('route:clear');
            $output .= "✓ Route cleared\n";
            Artisan::call('view:clear');
            $output .= "✓ View cleared\n";

            // Run migrations
            Artisan::call('migrate', ['--force' => true]);
            $output .= "✓ Migrations: " . trim(Artisan::output()) . "\n";

            // Storage link
            try {
                Artisan::call('storage:link');
                $output .= "✓ Storage link created\n";
            } catch (\Exception $e) {
                $output .= "⚠ Storage link: " . $e->getMessage() . "\n";
            }

            // Optimize
            Artisan::call('config:cache');
            $output .= "✓ Config cached\n";
            Artisan::call('route:cache');
            $output .= "✓ Routes cached\n";
            Artisan::call('view:cache');
            $output .= "✓ Views cached\n";

            return response()->json(['success' => true, 'message' => 'Full deployment completed!', 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Deploy failed: ' . $e->getMessage(), 'output' => $output], 500);
        }
    }
}
