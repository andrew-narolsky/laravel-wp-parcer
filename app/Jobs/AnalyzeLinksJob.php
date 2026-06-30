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
            ->where('is_active', true)
            ->get()
            ->map(fn(Link $link) => $analyzer->analyze($link));

        $total   = $results->count();
        $working = $results->filter->isWorking()->count();
        $broken  = $results->reject->isWorking()->count();

        Log::info('AnalyzeLinksJob complete', compact('total', 'working', 'broken'));

        Mail::to(env('REPORT_EMAIL'))->send(new LinksReportMail($results));
    }
}
