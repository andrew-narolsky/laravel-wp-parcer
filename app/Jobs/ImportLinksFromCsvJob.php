<?php

namespace App\Jobs;

use App\Models\Link;
use App\Models\Site;
use App\Services\CsvReader;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportLinksFromCsvJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    private const int CHUNK_SIZE = 500;

    public function __construct(public readonly string $filePath) {}

    public function handle(): void
    {
        $sites = Site::pluck('id', 'url')->toArray();

        $now   = now()->toDateTimeString();
        $chunk = [];
        $imported = $skipped = 0;

        foreach (CsvReader::rows($this->filePath) as $data) {
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
                Log::debug("Links import: site not found for URL: {$referringUrl}");
                continue;
            }

            $chunk[] = [
                'site_id'    => $siteId,
                'title'      => $anchor,
                'url'        => $targetUrl,
                'anchor'     => $anchor,
                'text'       => $content,
                'type'       => $destination === 'home' ? 'homepage' : 'post',
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

        Log::info("Links CSV import complete: {$imported} imported, {$skipped} skipped, {$queued} queued for publishing");
    }
}
