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

        // posts_available/homepage_available answer "can this site accept this kind of link
        // at all" — a site capability, not "is this specific link currently live". Skip when
        // there was nothing to actually check (e.g. wp_url cleared after we removed our own
        // content), and once a site has proven capable, don't let a later unrelated link
        // problem (or another removal) walk that back to false.
        if ($link->wp_url) {
            $field = $link->type === 'homepage' ? 'homepage_available' : 'posts_available';

            if ($link->site->$field !== true) {
                $link->site->update([$field => $result->status() === 'alive']);
            }
        }

        if ($this->notify) {
            Notification::send(User::all(), new LinkCheckFinished($link, $result->status()));
        }
    }
}