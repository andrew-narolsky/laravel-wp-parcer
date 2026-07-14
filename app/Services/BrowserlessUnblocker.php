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

        // 60000ms is the hard ceiling for the `timeout` param on the free Browserless plan —
        // requesting more gets rejected outright with a 400. Slower pages than that simply can't
        // be rendered on this plan; the client timeout below adds headroom for network/response
        // overhead beyond Browserless's own render budget, not to extend that budget itself.
        $endpoint = rtrim(config('services.browserless.url'), '/') . '/unblock?' . http_build_query([
            'token'   => config('services.browserless.token'),
            'timeout' => 60000,
        ]);

        try {
            $response = Http::timeout(75)->post($endpoint, [
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