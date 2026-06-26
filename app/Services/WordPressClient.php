<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WordPressClient
{
    public function testConnection(Site $site): array
    {
        $response = Http::withBasicAuth($site->login, $site->password)
            ->timeout(10)
            ->get("{$site->url}/wp-json/wp/v2/users/me");

        if (!$response->successful()) {
            throw new RuntimeException("HTTP {$response->status()}");
        }

        return $response->json();
    }

    public function publishPost(Site $site, string $title, string $content): array
    {
        $response = Http::withBasicAuth($site->login, $site->password)
            ->timeout(30)
            ->post("{$site->url}/wp-json/wp/v2/posts", [
                'title'   => $title,
                'content' => $content,
                'status'  => 'publish',
            ]);

        if (!$response->successful()) {
            throw new RuntimeException("HTTP {$response->status()}: " . $response->json('message', ''));
        }

        return $response->json();
    }

    public function updateFrontPage(Site $site, string $content): array
    {
        $http = Http::withBasicAuth($site->login, $site->password)->timeout(30);

        $settings = $http->get("{$site->url}/wp-json/wp/v2/settings");

        if (!$settings->successful()) {
            throw new RuntimeException("Cannot fetch settings: HTTP {$settings->status()}");
        }

        $pageId = $settings->json('page_on_front');

        if (!$pageId) {
            throw new RuntimeException("Front page is not set to a static page in WordPress settings.");
        }

        $page = $http->get("{$site->url}/wp-json/wp/v2/pages/{$pageId}");

        if (!$page->successful()) {
            throw new RuntimeException("Cannot fetch front page: HTTP {$page->status()}");
        }

        $existingContent = $page->json('content.raw') ?? $page->json('content.rendered', '');

        $response = $http->post("{$site->url}/wp-json/wp/v2/pages/{$pageId}", [
            'content' => $existingContent . "\n" . $content,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException("HTTP {$response->status()}: " . $response->json('message', ''));
        }

        return $response->json();
    }
}
