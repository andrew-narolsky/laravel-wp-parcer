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
}
