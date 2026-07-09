<?php

namespace App\Services;

use App\DTO\LinkCheckResult;
use App\Models\Link;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class LinkAvailabilityChecker
{
    public function check(Link $link): LinkCheckResult
    {
        if (!$link->wp_url) {
            return new LinkCheckResult($link, pageExists: false, hasLink: false, error: 'No published URL');
        }

        try {
            $response = Http::timeout(15)->get($link->wp_url);
        } catch (ConnectionException $e) {
            return new LinkCheckResult($link, pageExists: false, hasLink: false, error: 'Connection error: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            return new LinkCheckResult($link, pageExists: false, hasLink: false, error: "Cannot fetch page: HTTP {$response->status()}");
        }

        $body = $response->body();

        if ($this->looksLikeBotChallenge($body)) {
            return new LinkCheckResult(
                $link,
                pageExists: true,
                hasLink: false,
                blocked: true,
                error: 'Blocked by anti-bot protection — could not verify automatically',
            );
        }

        return new LinkCheckResult(
            link: $link,
            pageExists: true,
            hasLink: str_contains($body, $link->url),
        );
    }

    private function looksLikeBotChallenge(string $body): bool
    {
        $lower = strtolower($body);

        $markers = [
            'one moment, please',
            'just a moment',
            'checking your browser',
            'attention required',
            'ddos protection by',
            'enable javascript and cookies to continue',
            'verify you are human',
            'are you a robot',
            'cdn-cgi/challenge-platform',
            'challenges.cloudflare.com',
            'g-recaptcha',
            'h-captcha',
            'hcaptcha.com',
            'px-captcha',
        ];

        foreach ($markers as $marker) {
            if (str_contains($lower, $marker)) {
                return true;
            }
        }

        return str_contains($lower, 'settimeout') && str_contains($lower, 'location.reload');
    }
}