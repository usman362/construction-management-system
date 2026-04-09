<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BackupController extends Controller
{
    private function backupDir(): string
    {
        $dir = storage_path('app/backups');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public function index(): View
    {
        $dir = $this->backupDir();
        $files = glob($dir . '/*.sql');
        rsort($files);

        $backups = collect($files)->map(fn($f) => [
            'filename' => basename($f),
            'size' => filesize($f),
            'size_formatted' => $this->formatBytes(filesize($f)),
            'created_at' => date('Y-m-d H:i:s', filemtime($f)),
        ]);

        return view('admin.backups', ['backups' => $backups]);
    }

    public function create(): RedirectResponse
    {
        $db = config('database.connections.' . config('database.default'));
        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        $path = $this->backupDir() . '/' . $filename;

        $driver = config('database.default');

        if ($driver === 'sqlite') {
            // For SQLite, just copy the database file
            $sqlitePath = $db['database'];
            if (file_exists($sqlitePath)) {
                copy($sqlitePath, $path);
                return back()->with('success', "Backup created: {$filename}");
            }
            return back()->with('error', 'SQLite database file not found.');
        }

        // MySQL backup
        $host = $db['host'] ?? '127.0.0.1';
        $port = $db['port'] ?? '3306';
        $database = $db['database'];
        $username = $db['username'];
        $password = $db['password'] ?? '';

        $passwordArg = $password !== '' ? "-p" . escapeshellarg($password) : '';
        $cmd = sprintf(
            'mysqldump -h %s -P %s -u %s %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $passwordArg,
            escapeshellarg($database),
            escapeshellarg($path)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            @unlink($path);
            return back()->with('error', 'Backup failed: ' . implode("\n", $output));
        }

        return back()->with('success', "Backup created: {$filename} (" . $this->formatBytes(filesize($path)) . ")");
    }

    public function download(string $filename)
    {
        $path = $this->backupDir() . '/' . basename($filename);
        if (!file_exists($path)) {
            abort(404, 'Backup not found.');
        }
        return response()->download($path);
    }

    public function destroy(string $filename): RedirectResponse
    {
        $path = $this->backupDir() . '/' . basename($filename);
        if (file_exists($path)) {
            unlink($path);
        }
        return back()->with('success', 'Backup deleted.');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
