<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Admin-facing maintenance tools — cache clearing, storage symlink, etc.
 * Exposed as a small panel on the profile/settings page so non-SSH deployments
 * (shared hosting, cPanel) can recover from "page not updating" after a deploy
 * without needing terminal access.
 */
class SystemMaintenanceController extends Controller
{
    /**
     * Run one of the supported artisan cache/reset commands.
     * Mapped so the client can only trigger safe, idempotent commands.
     */
    public function clearCache(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:all,config,route,view,cache,compiled',
        ]);

        $map = [
            'all'      => ['optimize:clear'],
            'config'   => ['config:clear'],
            'route'    => ['route:clear'],
            'view'     => ['view:clear'],
            'cache'    => ['cache:clear'],
            'compiled' => ['clear-compiled'],
        ];

        $type = $request->input('type');
        $commands = $map[$type];

        try {
            $output = [];
            foreach ($commands as $cmd) {
                Artisan::call($cmd);
                $output[] = $cmd . ': ' . trim(Artisan::output());
            }
            return response()->json([
                'message' => $this->successLabel($type),
                'output'  => $output,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Failed to run command: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create the public/storage symlink (php artisan storage:link).
     * Safe to re-run — will report "already exists" if the link is present.
     */
    public function storageLink(): JsonResponse
    {
        $publicStorage = public_path('storage');

        if (file_exists($publicStorage) || is_link($publicStorage)) {
            return response()->json([
                'message' => 'Storage symlink already exists at public/storage.',
                'already_exists' => true,
            ]);
        }

        try {
            Artisan::call('storage:link');
            $out = trim(Artisan::output());

            return response()->json([
                'message' => 'Storage symlink created successfully.',
                'output'  => $out,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Failed to create storage symlink: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Snapshot of current caching state so the UI can show what's compiled.
     * Keeps this lightweight — just size/exists checks, no file reads.
     */
    public function status(): JsonResponse
    {
        $configCache = base_path('bootstrap/cache/config.php');
        $routeCache  = base_path('bootstrap/cache/routes-v7.php');
        $eventsCache = base_path('bootstrap/cache/events.php');
        $viewDir     = storage_path('framework/views');
        $storageLink = public_path('storage');

        return response()->json([
            'config_cached'  => file_exists($configCache),
            'route_cached'   => file_exists($routeCache),
            'events_cached'  => file_exists($eventsCache),
            'compiled_views' => File::isDirectory($viewDir) ? count(File::files($viewDir)) : 0,
            'storage_linked' => is_link($storageLink) || (file_exists($storageLink) && is_dir($storageLink)),
            'app_env'        => config('app.env'),
            'app_debug'      => (bool) config('app.debug'),
            'php_version'    => PHP_VERSION,
            'laravel_version'=> app()->version(),
        ]);
    }

    private function successLabel(string $type): string
    {
        return match ($type) {
            'all'      => 'All caches cleared (config, route, view, app, compiled).',
            'config'   => 'Configuration cache cleared.',
            'route'    => 'Route cache cleared.',
            'view'     => 'Compiled views cleared.',
            'cache'    => 'Application cache cleared.',
            'compiled' => 'Compiled services cleared.',
        };
    }
}
