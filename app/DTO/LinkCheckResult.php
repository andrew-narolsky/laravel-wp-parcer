<?php

namespace App\DTO;

use App\Models\Link;

readonly class LinkCheckResult
{
    public function __construct(
        public Link $link,
        public bool $pageExists,
        public bool $hasLink,
        public bool $blocked = false,
        public ?string $error = null,
    ) {}

    public function isWorking(): bool
    {
        return $this->pageExists && $this->hasLink && !$this->blocked;
    }

    public function status(): string
    {
        if ($this->blocked) {
            return 'blocked';
        }

        return $this->isWorking() ? 'alive' : 'not_found';
    }

    public function failReason(): string
    {
        if ($this->error) {
            return $this->error;
        }
        if ($this->blocked) {
            return 'Blocked by anti-bot protection';
        }
        if (!$this->pageExists) {
            return 'Published page not found';
        }
        if (!$this->hasLink) {
            return 'Link not found on the page';
        }
        return '';
    }
}
