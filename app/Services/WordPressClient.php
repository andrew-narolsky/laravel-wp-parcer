<?php

namespace App\Services;

use App\Models\Site;
use RuntimeException;

class WordPressClient
{
    public function testConnection(Site $site): array
    {
        $response = WordPressHttpClient::for($site)
            ->get("{$site->url}/wp-json/wp/v2/users/me");

        if (!$response->successful()) {
            throw new RuntimeException("HTTP {$response->status()}");
        }

        return $response->json();
    }
}
