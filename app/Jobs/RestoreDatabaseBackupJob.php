<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\BackupFailed;
use App\Notifications\BackupRestored;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class RestoreDatabaseBackupJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public readonly string $filename)
    {
        $this->onQueue('backups');
    }

    public function handle(): void
    {
        $path = 'backups/' . $this->filename;

        if (!Storage::disk('local')->exists($path)) {
            throw new RuntimeException("Backup file not found: {$this->filename}");
        }

        $data = json_decode(Storage::disk('local')->get($path), true);

        if (!is_array($data) || !isset($data['sites'], $data['links']) || !is_array($data['sites']) || !is_array($data['links'])) {
            throw new RuntimeException("Backup file is malformed: {$this->filename}");
        }

        // DELETE (not TRUNCATE) so the whole thing participates in the transaction and rolls
        // back cleanly on failure instead of leaving the tables half-restored. Children (links)
        // go before parents (sites) on the way out, parents before children on the way back in
        // — correct FK order, so there's no need to touch foreign key checks at all.
        DB::transaction(function () use ($data) {
            DB::table('links')->delete();
            DB::table('sites')->delete();

            foreach (array_chunk($data['sites'], 500) as $chunk) {
                DB::table('sites')->insert($chunk);
            }

            foreach (array_chunk($data['links'], 500) as $chunk) {
                DB::table('links')->insert($chunk);
            }
        });

        Log::info('Database backup restored', [
            'filename' => $this->filename,
            'sites'    => count($data['sites']),
            'links'    => count($data['links']),
        ]);

        Notification::send(User::all(), new BackupRestored($this->filename, count($data['sites']), count($data['links'])));
    }

    public function failed(Throwable $exception): void
    {
        Notification::send(User::all(), new BackupFailed('restore', $exception->getMessage()));
    }
}