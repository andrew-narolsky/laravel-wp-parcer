<?php

namespace App\Services\Publishers;

use App\Contracts\LinkPublisherContract;
use App\Models\Link;
use App\Models\Site;
use App\Services\WordPressHttpClient;
use RuntimeException;

class HomepagePublisher implements LinkPublisherContract
{
    public function publish(Site $site, Link $link): array
    {
        $http = WordPressHttpClient::for($site, 30);

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
            'content' => $existingContent . "\n" . $link->text,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException("HTTP {$response->status()}: " . $response->json('message', ''));
        }

        return $response->json();
    }
}
