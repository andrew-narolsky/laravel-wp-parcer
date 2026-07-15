<?php

namespace App\Jobs;

use App\Models\Link;
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

    public int $timeout = 3600;

    public function __construct(public readonly string $filePath, public readonly string $linkType = 'post') {}

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

        $link = Link::updateOrCreate(
            [
                'site_id' => $site->id,
                'url'     => $matches[1],
                'anchor'  => trim(strip_tags($matches[2])),
                'type'    => $this->linkType,
            ],
            [
                'title' => $title,
                'text'  => $description,
                'image' => trim($data['image'] ?? '') ?: null,
            ]
        );

        dispatch(new PublishLinkJob($link));
    }
}
