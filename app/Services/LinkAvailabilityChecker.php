<?php

namespace App\Services;

use App\DTO\LinkCheckResult;
use App\Models\Link;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LinkAvailabilityChecker
{
    public function __construct(private readonly BrowserlessUnblocker $unblocker) {}

    public function check(Link $link): LinkCheckResult
    {
        if (!$link->wp_url) {
            return new LinkCheckResult($link, pageExists: false, hasLink: false, error: 'No published URL');
        }

        try {
            $body = $this->fetchBody($link->wp_url);
        } catch (RuntimeException $e) {
            // Unlike a plain-HTTP failure (a real signal the site/page is down), a failed
            // Browserless call (quota exhausted, API outage, timeout) says nothing about the link
            // itself — rethrowing leaves check_status untouched instead of recording a false
            // "not_found". allowFailures() on the analysis batch means this doesn't block the rest.
            if ($this->useBrowserless()) {
                throw $e;
            }

            return new LinkCheckResult($link, pageExists: false, hasLink: false, error: $e->getMessage());
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
                compromised: true,
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

    // Public so callers that need to check arbitrary URLs (e.g. a candidate post found by
    // title search, whose real URL isn't known ahead of time) get the same fetch behavior
    // — plain HTTP or Browserless, per LINK_CHECK_DRIVER — as a normal availability check.
    // Throws on any failure; check() decides what that means for its LinkCheckResult.
    public function fetchBody(string $url): string
    {
        if ($this->useBrowserless()) {
            if (!$this->unblocker->isConfigured()) {
                throw new RuntimeException('LINK_CHECK_DRIVER=browserless but BROWSERLESS_TOKEN is not set');
            }

            $body = $this->unblocker->fetch($url);

            if ($body === null) {
                throw new RuntimeException('Browserless request failed — see logs for details');
            }

            return $body;
        }

        try {
            $response = Http::timeout(15)->get($url);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Connection error: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            throw new RuntimeException("Cannot fetch page: HTTP {$response->status()}");
        }

        return $response->body();
    }

    // Public so a match against arbitrary fetched content (e.g. a candidate WordPress post
    // found by title/content search) can reuse the exact same "is this link really there"
    // criterion used for a normal availability check — not a looser substring match.
    public function hasLink(string $body, Link $link): bool
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

        // Deliberately excludes generic captcha/bot-management script markers (g-recaptcha,
        // hcaptcha, cdn-cgi/challenge-platform, challenges.cloudflare.com, etc.) and a former
        // setTimeout+location.reload combo check — all showed up on plenty of legitimate,
        // content-rich pages (a contact-form captcha, a lightbox plugin, Cloudflare's background
        // bot-management script) with nothing to do with an actual bot-challenge wall. Only
        // specific interstitial-page phrases — text a real "please wait" page actually shows —
        // belong here.
        $markers = [
            'one moment, please',
            'just a moment',
            'checking your browser',
            'attention required',
            'ddos protection by',
            'enable javascript and cookies to continue',
            'verify you are human',
            'are you a robot',
        ];

        foreach ($markers as $marker) {
            if (str_contains($lower, $marker)) {
                return true;
            }
        }

        return false;
    }
}