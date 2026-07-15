<?php

namespace App\Jobs;

use App\Models\Link;
use App\Models\User;
use App\Notifications\LinkCheckFinished;
use App\Services\LinkAvailabilityChecker;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class AnalyzeLinkJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    public int $timeout = 150;

    /** @param bool $notify Only true for a manual single-link "Check" click — bulk analysis runs stay quiet to avoid spamming a toast per link. */
    public function __construct(public readonly int $linkId, public readonly bool $notify = false) {}

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

        if ($this->notify) {
            Notification::send(User::all(), new LinkCheckFinished($link, $result->status()));
        }
    }
}