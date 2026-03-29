<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Throwable;

class SystemDeployController extends Controller
{
    /**
     * Run `git pull` in the application base path.
     */
    public function gitPull(): JsonResponse
    {
        try {
            $result = Process::timeout(300)->path(base_path())->run('git pull 2>&1');

            $out = trim($result->output() ?: $result->errorOutput());

            return response()->json([
                'success' => $result->successful(),
                'exit_code' => $result->exitCode(),
                'output' => $out,
            ], $result->successful() ? 200 : 500);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run `php artisan migrate --force`.
     */
    public function migrate(): JsonResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);

            return response()->json([
                'success' => true,
                'output' => trim(Artisan::output()),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run `php artisan db:seed --force`.
     * Optional JSON/form field "class" for a specific seeder (e.g. Database\\Seeders\\RoleSeeder).
     */
    public function seed(Request $request): JsonResponse
    {
        $class = $request->input('class');

        try {
            $params = ['--force' => true];
            if ($class !== null && $class !== '') {
                if (! is_string($class) || ! preg_match('/^[A-Za-z0-9\\\\]+$/', $class)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid seeder class name.',
                    ], 422);
                }
                $params['--class'] = $class;
            }

            Artisan::call('db:seed', $params);

            return response()->json([
                'success' => true,
                'output' => trim(Artisan::output()),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
