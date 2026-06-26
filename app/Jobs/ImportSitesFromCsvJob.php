<?php

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SplFileObject;

class ImportSitesFromCsvJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    private const int CHUNK_SIZE = 500;

    public function __construct(public readonly string $filePath) {}

    public function handle(): void
    {
        $fullPath = Storage::path($this->filePath);

        if (!file_exists($fullPath)) {
            Log::error("CSV import file not found: $fullPath");
            return;
        }

        $file = new SplFileObject($fullPath);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $header = null;
        $chunk = [];
        $imported = 0;
        $skipped = 0;

        foreach ($file as $row) {
            if ($header === null) {
                $header = array_map('trim', $row);
                continue;
            }

            if (!is_array($row) || count($row) !== count($header)) {
                continue;
            }

            $data = array_combine($header, $row);

            if (!empty($data['wp_old_version'])) {
                $skipped++;
                continue;
            }

            $url = rtrim(trim($data['url'] ?? ''), '/');
            if (empty($url)) {
                continue;
            }

            $chunk[] = [
                'name'      => parse_url($url, PHP_URL_HOST) ?? $url,
                'url'       => $url,
                'login'     => trim($data['username'] ?? ''),
                'password'  => trim($data['app_password'] ?? ''),
                'is_active' => true,
            ];

            $imported++;

            if (count($chunk) >= self::CHUNK_SIZE) {
                Site::upsert($chunk, ['url'], ['name', 'login', 'password', 'is_active']);
                $chunk = [];
            }
        }

        if ($chunk) {
            Site::upsert($chunk, ['url'], ['name', 'login', 'password', 'is_active']);
        }

        Storage::delete($this->filePath);

        Log::info("CSV import complete: $imported imported, $skipped skipped (wp_old_version set)");
    }
}