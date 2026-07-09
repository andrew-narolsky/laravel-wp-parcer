<?php

namespace App\Jobs;

use App\Mail\LinksReportMail;
use App\Models\Link;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLinksAnalysisReportJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $links = Link::with('site')
            ->where('status', 'published')
            ->whereNotNull('checked_at')
            ->get();

        Log::info('AnalyzeLinksJob complete', [
            'total'   => $links->count(),
            'alive'   => $links->where('check_status', 'alive')->count(),
            'broken'  => $links->where('check_status', 'not_found')->count(),
        ]);

        Mail::to(config('services.report_email'))->send(new LinksReportMail($links));
    }
}