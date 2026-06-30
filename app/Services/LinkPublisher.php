<?php

namespace App\Services;

use App\Contracts\LinkPublisherContract;
use App\Models\Link;

class LinkPublisher
{
    /** @param array<string, LinkPublisherContract> $publishers */
    public function __construct(private readonly array $publishers) {}

    public function publish(Link $link): array
    {
        $publisher = $this->publishers[$link->type]
            ?? throw new \InvalidArgumentException("No publisher registered for link type: {$link->type}");

        return $publisher->publish($link->site, $link);
    }
}
