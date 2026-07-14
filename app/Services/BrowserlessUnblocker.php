<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrowserlessUnblocker
{
    public function isConfigured(): bool
    {
        return filled(config('services.browserless.token'));
    }

    /** Renders the page in a real browser via Browserless's /unblock API, bypassing bot-detection challenges. Returns null if unconfigured or the call fails — callers should fall back to the plain-HTTP result. */
    public function fetch(string $url): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $endpoint = rtrim(config('services.browserless.url'), '/') . '/unblock?' . http_build_query([
            'token' => config('services.browserless.token'),
        ]);

        try {
            $response = Http::timeout(60)->post($endpoint, [
                'url'     => $url,
                'content' => true,
            ]);
        } catch (ConnectionException $e) {
            Log::warning('BrowserlessUnblocker: connection error', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }

        if (!$response->successful()) {
            Log::warning('BrowserlessUnblocker: request failed', ['url' => $url, 'status' => $response->status(), 'body' => $response->body()]);
            return null;
        }

        return $response->json('content');
    }
}