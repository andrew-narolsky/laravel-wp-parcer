<?php

namespace App\Services;

use App\DTO\LinkCheckResult;
use App\Models\Link;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class LinkAvailabilityChecker
{
    public function __construct(private readonly BrowserlessUnblocker $unblocker) {}

    public function check(Link $link): LinkCheckResult
    {
        if (!$link->wp_url) {
            return new LinkCheckResult($link, pageExists: false, hasLink: false, error: 'No published URL');
        }

        if ($this->useBrowserless()) {
            $body = $this->unblocker->fetch($link->wp_url);

            if ($body === null) {
                return new LinkCheckResult($link, pageExists: false, hasLink: false, error: 'Browserless request failed or is not configured');
            }
        } else {
            try {
                $response = Http::timeout(15)->get($link->wp_url);
            } catch (ConnectionException $e) {
                return new LinkCheckResult($link, pageExists: false, hasLink: false, error: 'Connection error: ' . $e->getMessage());
            }

            if (!$response->successful()) {
                return new LinkCheckResult($link, pageExists: false, hasLink: false, error: "Cannot fetch page: HTTP {$response->status()}");
            }

            $body = $response->body();
        }

        if ($this->looksLikeBotChallenge($body)) {
            return new LinkCheckResult(
                $link,
                pageExists: true,
                hasLink: false,
                blocked: true,
                error: 'Blocked by anti-bot protection — could not verify automatically',
            );
        }

        if ($this->looksLikeSpamCloaked($body)) {
            return new LinkCheckResult(
                $link,
                pageExists: true,
                hasLink: false,
                blocked: true,
                error: 'Page looks compromised with a hidden spam link farm (cloaking) — cannot reliably verify visibility',
            );
        }

        return new LinkCheckResult(
            link: $link,
            pageExists: true,
            hasLink: $this->hasLink($body, $link),
        );
    }

    private function useBrowserless(): bool
    {
        return config('services.link_check_driver') === 'browserless';
    }

    private function hasLink(string $body, Link $link): bool
    {
        if (!preg_match_all('/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $body, $matches, PREG_SET_ORDER)) {
            return false;
        }

        foreach ($matches as $match) {
            $href = trim($match[1]);
            $text = trim(strip_tags($match[2]));

            if ($href === $link->url && str_contains($text, $link->anchor)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeSpamCloaked(string $body): bool
    {
        // Off-screen-positioned <a> tags are a common technique for hiding injected spam
        // link farms from human visitors while keeping them crawlable — a handful can be
        // a legitimate accessibility trick, but dozens/hundreds signal a compromised page.
        preg_match_all(
            '/<a\s[^>]*style=["\'][^"\']*position\s*:\s*absolute[^"\']*(?:top|left)\s*:\s*-\d{3,}px[^"\']*["\'][^>]*>/i',
            $body,
            $matches
        );

        return count($matches[0]) >= 5;
    }

    private function looksLikeBotChallenge(string $body): bool
    {
        $lower = strtolower($body);

        // Deliberately excludes generic captcha-widget markers (g-recaptcha, hcaptcha, etc.) —
        // those show up on plenty of legitimate pages via a normal contact/comment form and
        // don't mean the whole page is a bot-challenge wall. Only specific interstitial-page
        // phrases belong here.
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
        ];

        foreach ($markers as $marker) {
            if (str_contains($lower, $marker)) {
                return true;
            }
        }

        return str_contains($lower, 'settimeout') && str_contains($lower, 'location.reload');
    }
}