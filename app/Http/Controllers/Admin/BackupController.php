<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CreateDatabaseBackupJob;
use App\Jobs\RestoreDatabaseBackupJob;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(): View
    {
        $backups = collect(Storage::disk('local')->files('backups'))
            ->filter(fn (string $path) => str_ends_with($path, '.json'))
            ->map(fn (string $path) => [
                'filename'      => basename($path),
                'size'          => Storage::disk('local')->size($path),
                'last_modified' => Storage::disk('local')->lastModified($path),
            ])
            ->sortByDesc('last_modified')
            ->values();

        return view('admin.backups.index', compact('backups'));
    }

    public function store(): JsonResponse
    {
        dispatch(new CreateDatabaseBackupJob());

        return response()->json(['message' => 'Backup queued.']);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:json,txt', 'max:102400'],
        ]);

        $content = file_get_contents($request->file('backup_file')->getRealPath());
        $data    = json_decode($content, true);

        if (!is_array($data) || !isset($data['sites'], $data['links']) || !is_array($data['sites']) || !is_array($data['links'])) {
            return response()->json(['message' => 'Invalid backup file — expected JSON with "sites" and "links" arrays.'], 422);
        }

        $filename = 'uploaded-' . now()->format('Y-m-d-His') . '.json';

        Storage::disk('local')->put('backups/' . $filename, $content);

        return response()->json(['message' => "Backup uploaded: {$filename}. It won't be restored automatically — use the Restore button when ready."]);
    }

    public function download(string $filename): StreamedResponse
    {
        return Storage::disk('local')->download($this->resolvePath($filename));
    }

    public function restore(string $filename): JsonResponse
    {
        $this->resolvePath($filename);

        dispatch(new RestoreDatabaseBackupJob($filename));

        return response()->json(['message' => 'Restore queued.']);
    }

    public function destroy(string $filename): JsonResponse
    {
        Storage::disk('local')->delete($this->resolvePath($filename));

        return response()->json(['message' => "Deleted {$filename}."]);
    }

    /** basename() strips any directory-traversal attempt regardless of what the route regex already blocks. */
    private function resolvePath(string $filename): string
    {
        $path = 'backups/' . basename($filename);

        abort_unless(Storage::disk('local')->exists($path), 404);

        return $path;
    }
}