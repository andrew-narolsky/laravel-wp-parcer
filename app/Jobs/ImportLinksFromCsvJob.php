<?php

namespace App\Jobs;

use App\Models\Link;
use App\Models\Site;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SplFileObject;

class ImportLinksFromCsvJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    private const int CHUNK_SIZE = 500;

    public function __construct(public readonly string $filePath) {}

    public function handle(): void
    {
        $fullPath = Storage::path($this->filePath);

        if (!file_exists($fullPath)) {
            Log::error("Links CSV import file not found: $fullPath");
            return;
        }

        // Preload all sites into memory for fast lookup by URL
        $sites = Site::pluck('id', 'url')->toArray();

        $file = new SplFileObject($fullPath);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $header = null;
        $chunk = [];
        $imported = 0;
        $skipped = 0;
        $now = now()->toDateTimeString();

        foreach ($file as $row) {
            if ($header === null) {
                $header = array_map('trim', $row);
                continue;
            }

            if (!is_array($row) || count($row) !== count($header)) {
                continue;
            }

            $data = array_combine($header, $row);

            $referringUrl = rtrim(trim($data['Referring page URL'] ?? ''), '/');
            $destination  = strtolower(trim($data['Destination'] ?? ''));
            $targetUrl    = trim($data['Target URL'] ?? '');
            $anchor       = trim($data['Anchor'] ?? '');
            $content      = trim($data['Content'] ?? '');

            if (empty($referringUrl) || empty($targetUrl) || empty($anchor) || empty($content)) {
                $skipped++;
                continue;
            }

            $siteId = $sites[$referringUrl] ?? null;
            if (!$siteId) {
                $skipped++;
                Log::debug("Links import: site not found for URL: $referringUrl");
                continue;
            }

            $type = $destination === 'home' ? 'homepage' : 'post';

            $linked = preg_replace(
                '/' . preg_quote($anchor, '/') . '/',
                '<a href="' . $targetUrl . '">' . $anchor . '</a>',
                $content,
                1
            );

            $linkedContent = '<p>' . $linked . '</p>';

            $chunk[] = [
                'site_id'    => $siteId,
                'title'      => $anchor,
                'url'        => $targetUrl,
                'anchor'     => $anchor,
                'text'       => $linkedContent,
                'type'       => $type,
                'is_active'  => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $imported++;

            if (count($chunk) >= self::CHUNK_SIZE) {
                Link::upsert($chunk, ['site_id', 'url', 'anchor'], ['title', 'text', 'type', 'updated_at']);
                $chunk = [];
            }
        }

        if ($chunk) {
            Link::upsert($chunk, ['site_id', 'url', 'anchor'], ['title', 'text', 'type', 'updated_at']);
        }

        Storage::delete($this->filePath);

        $queued = 0;
        Link::where('updated_at', $now)
            ->where('is_active', false)
            ->each(function (Link $link) use (&$queued) {
                dispatch(new PublishLinkJob($link));
                $queued++;
            });

        Log::info("Links CSV import complete: $imported imported, $skipped skipped, $queued queued for publishing");
    }
}