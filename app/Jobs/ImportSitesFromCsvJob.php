<?php

namespace App\Jobs;

use App\Models\Link;
use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use App\Notifications\ImportFinished;
use App\Notifications\ImportStarted;
use App\Services\CsvReader;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ImportSitesFromCsvJob implements ShouldQueue
{
    use Queueable;

    // A retry would reprocess the whole CSV and redispatch PublishLinkJob for every row again.
    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(
        public readonly string $filePath,
        public readonly string $linkType = 'post',
        public readonly ?int $projectId = null,
    ) {}

    public function handle(): void
    {
        Notification::send(User::all(), new ImportStarted($this->linkType));

        $imported = $skipped = 0;

        foreach (CsvReader::rows($this->filePath) as $data) {
            $url      = rtrim(trim($data['site'] ?? ''), '/');
            $login    = trim($data['login'] ?? '');
            $password = trim($data['password'] ?? '');

            if (empty($url) || empty($login) || empty($password)) {
                $skipped++;
                continue;
            }

            $site = Site::firstOrCreate(
                ['url' => $url],
                [
                    'name'      => parse_url($url, PHP_URL_HOST) ?? $url,
                    'login'     => $login,
                    'password'  => $password,
                    'is_active' => true,
                ]
            );

            $this->createAndPublishLink($site, $data);

            $imported++;
        }

        Storage::delete($this->filePath);

        Log::info("Sites CSV import complete: {$imported} imported, {$skipped} skipped");

        Notification::send(User::all(), new ImportFinished($this->linkType, $imported, $skipped));
    }

    private function createAndPublishLink(Site $site, array $data): void
    {
        $title       = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');

        if (empty($description) || ($this->linkType === 'post' && empty($title))) {
            Log::warning("Sites CSV import: missing title/description for site {$site->url}");
            return;
        }

        if (!preg_match('/<a\s[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', $description, $matches)) {
            Log::warning("Sites CSV import: no link found in description for site {$site->url}");
            return;
        }

        $projectId = $this->projectId ?? Project::resolveForUrl($matches[1])?->id;

        $link = Link::updateOrCreate(
            [
                'site_id' => $site->id,
                'url'     => $matches[1],
                'anchor'  => trim(strip_tags($matches[2])),
                'type'    => $this->linkType,
            ],
            [
                'title'      => $title,
                'text'       => $description,
                'image'      => trim($data['image'] ?? '') ?: null,
                'project_id' => $projectId,
            ]
        );

        // Same (site_id, url, anchor, type) row as an already-published earlier import —
        // publishing again would create a duplicate post/homepage-fragment on the site.
        if (!$link->wasRecentlyCreated && $link->status === 'published') {
            Log::info("Sites CSV import: skipping publish, link already published", ['link_id' => $link->id]);
            return;
        }

        dispatch(new PublishLinkJob($link));
    }
}
