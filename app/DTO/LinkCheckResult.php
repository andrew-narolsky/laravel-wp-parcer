<?php

namespace App\DTO;

use App\Models\Link;

readonly class LinkCheckResult
{
    public function __construct(
        public Link $link,
        public bool $postExists,
        public bool $hasLink,
        public ?string $error = null,
    ) {}

    public function isWorking(): bool
    {
        return $this->postExists && $this->hasLink;
    }

    public function failReason(): string
    {
        if ($this->error) {
            return $this->error;
        }
        if (!$this->postExists) {
            return 'Post not found';
        }
        if (!$this->hasLink) {
            return 'Link not found in content';
        }
        return '';
    }
}
