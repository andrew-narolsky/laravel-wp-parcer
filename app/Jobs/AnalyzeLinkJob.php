<?php

namespace App\Jobs;

use App\Models\Link;
use App\Services\LinkAvailabilityChecker;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeLinkJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    public int $timeout = 150;

    public function __construct(public readonly int $linkId) {}

    public function handle(LinkAvailabilityChecker $checker): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $link = Link::find($this->linkId);
        if (!$link) {
            return;
        }

        $result = $checker->check($link);

        $link->update([
            'check_status' => $result->status(),
            'check_error'  => $result->isWorking() ? null : $result->failReason(),
            'checked_at'   => now(),
        ]);

        $field = $link->type === 'homepage' ? 'homepage_available' : 'posts_available';
        $link->site()->update([$field => $result->status() === 'alive']);
    }
}