<?php

namespace App\Jobs;

use App\Mail\LinksReportMail;
use App\Models\Link;
use App\Models\User;
use App\Notifications\AnalyzeFinished;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendLinksAnalysisReportJob implements ShouldQueue
{
    use Queueable;

    /** @param array<int> $linkIds The exact links analyzed in this run — reported by ID, not re-filtered, so the report reflects them even if their check_status changed during analysis. */
    public function __construct(public readonly array $linkIds) {}

    public function handle(): void
    {
        $links = Link::with('site')
            ->whereIn('id', $this->linkIds)
            ->whereNotNull('checked_at')
            ->get();

        $total  = $links->count();
        $alive  = $links->where('check_status', 'alive')->count();
        $broken = $links->where('check_status', 'not_found')->count();

        Log::info('AnalyzeLinksJob complete', ['total' => $total, 'alive' => $alive, 'broken' => $broken]);

        Mail::to(config('services.report_email'))->send(new LinksReportMail($links));

        Notification::send(User::all(), new AnalyzeFinished($total, $alive, $broken));
    }
}