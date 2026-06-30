<?php

namespace App\Services;

use App\Contracts\LinkCheckerContract;
use App\DTO\LinkCheckResult;
use App\Models\Link;
use Illuminate\Support\Facades\Log;

class LinkAnalyzer
{
    /** @param array<string, LinkCheckerContract> $checkers */
    public function __construct(private readonly array $checkers) {}

    public function analyze(Link $link): LinkCheckResult
    {
        $checker = $this->checkers[$link->type]
            ?? throw new \InvalidArgumentException("No checker registered for link type: {$link->type}");

        try {
            return $checker->check($link->site, $link);
        } catch (\Throwable $e) {
            Log::warning('LinkAnalyzer: check failed', [
                'link_id' => $link->id,
                'type'    => $link->type,
                'error'   => $e->getMessage(),
            ]);

            return new LinkCheckResult(
                link: $link,
                postExists: false,
                hasLink: false,
                error: $e->getMessage(),
            );
        }
    }
}
