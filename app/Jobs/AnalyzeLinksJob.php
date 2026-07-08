<?php

namespace App\Jobs;

use App\Mail\LinksReportMail;
use App\Models\Link;
use App\Services\LinkAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AnalyzeLinksJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public function handle(LinkAnalyzer $analyzer): void
    {
        $results = Link::with('site')
            ->where('status', 'published')
            ->lazy(100)
            ->map(fn(Link $link) => $analyzer->analyze($link))
            ->collect();

        $total   = $results->count();
        $working = $results->filter->isWorking()->count();
        $broken  = $results->reject->isWorking()->count();

        Log::info('AnalyzeLinksJob complete', compact('total', 'working', 'broken'));

        Mail::to(config('services.report_email'))->send(new LinksReportMail($results));
    }
}
