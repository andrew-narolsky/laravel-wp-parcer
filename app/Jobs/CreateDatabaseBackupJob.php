<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\BackupCreated;
use App\Notifications\BackupFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CreateDatabaseBackupJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('backups');
    }

    public function handle(): void
    {
        // Raw query builder, not the Eloquent models — Site hides `password` from array/JSON
        // serialization, and a backup needs the real column values to be useful for restore.
        $sites    = DB::table('sites')->get()->map(fn ($row) => (array) $row)->all();
        $links    = DB::table('links')->get()->map(fn ($row) => (array) $row)->all();
        $projects = DB::table('projects')->get()->map(fn ($row) => (array) $row)->all();

        $filename = 'backup-' . now()->format('Y-m-d-His') . '.json';

        Storage::disk('local')->put('backups/' . $filename, json_encode([
            'created_at' => now()->toIso8601String(),
            'sites'      => $sites,
            'links'      => $links,
            'projects'   => $projects,
        ], JSON_PRETTY_PRINT));

        Log::info('Database backup created', ['filename' => $filename, 'sites' => count($sites), 'links' => count($links), 'projects' => count($projects)]);

        Notification::send(User::all(), new BackupCreated($filename, count($sites), count($links), count($projects)));
    }

    public function failed(Throwable $exception): void
    {
        Notification::send(User::all(), new BackupFailed('creation', $exception->getMessage()));
    }
}