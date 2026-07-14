<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLinkRequest;
use App\Http\Requests\Admin\UpdateLinkRequest;
use App\Jobs\AnalyzeLinkJob;
use App\Jobs\AnalyzeLinksJob;
use App\Jobs\PublishLinkJob;
use App\Models\Link;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LinkController extends Controller
{
    private const SORTABLE = [
        'site'          => 'sites.name',
        'type'          => 'links.type',
        'wp_url'        => 'links.wp_url',
        'status'        => 'links.status',
        'failed_reason' => 'links.failed_reason',
        'check_status'  => 'links.check_status',
        'created_at'    => 'links.created_at',
    ];

    public function index(Request $request): View
    {
        $sort        = $request->string('sort')->toString();
        $direction   = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $type        = $request->string('type')->toString();
        $status      = $request->string('status')->toString();
        $checkStatus = $request->string('check_status')->toString();

        if (!array_key_exists($sort, self::SORTABLE)) {
            $sort = 'created_at';
        }

        if (!in_array($type, ['post', 'homepage'], true)) {
            $type = '';
        }

        if ($status !== 'published') {
            $status = '';
        }

        if ($checkStatus !== 'alive') {
            $checkStatus = '';
        }

        $links = Link::query()
            ->leftJoin('sites', 'sites.id', '=', 'links.site_id')
            ->select('links.*')
            ->with('site')
            ->when($type, fn ($query) => $query->where('links.type', $type))
            ->when($status, fn ($query) => $query->where('links.status', $status))
            ->when($checkStatus, fn ($query) => $query->where('links.check_status', $checkStatus))
            ->orderBy(self::SORTABLE[$sort], $direction)
            ->paginate(50)
            ->withQueryString();

        return view('admin.links.index', compact('links', 'sort', 'direction', 'type', 'status', 'checkStatus'));
    }

    public function create(): View
    {
        $sites = Site::where('is_active', true)->orderBy('name')->get();

        return view('admin.links.create', compact('sites'));
    }

    public function store(StoreLinkRequest $request): RedirectResponse
    {
        $link = Link::create($request->validated());

        return redirect()->route('admin.links.edit', $link)->with('success', 'Link added');
    }

    public function edit(Link $link): View
    {
        $sites = Site::where('is_active', true)->orderBy('name')->get();

        return view('admin.links.edit', compact('link', 'sites'));
    }

    public function update(UpdateLinkRequest $request, Link $link): RedirectResponse
    {
        $link->update($request->validated());

        return redirect()->route('admin.links.edit', $link)->with('success', 'Link updated');
    }

    public function destroy(Link $link): RedirectResponse
    {
        $link->delete();

        return redirect()->route('admin.links.index')->with('success', 'Link deleted');
    }

    public function publish(Link $link): RedirectResponse
    {
        $link->update(['status' => 'pending', 'failed_reason' => null]);

        dispatch(new PublishLinkJob($link));

        return redirect()->back()->with('success', 'Queued for publishing.');
    }

    public function check(Link $link): RedirectResponse
    {
        $link->update(['check_status' => 'unknown', 'check_error' => null]);

        dispatch(new AnalyzeLinkJob($link->id));

        return redirect()->back()->with('success', 'Queued for status check.');
    }

    public function analyze(): RedirectResponse
    {
        dispatch(new AnalyzeLinksJob());

        return redirect()->back()->with('success', 'Analysis started. Report will be sent to ' . config('services.report_email') . '.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'links-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['id', 'site', 'title', 'url', 'wp_url', 'anchor', 'text', 'image', 'type', 'status', 'failed_reason', 'check_status', 'check_error', 'checked_at']);

            Link::with('site')->orderBy('id')->lazy(500)->each(function (Link $link) use ($handle) {
                fputcsv($handle, [
                    $link->id,
                    $link->site->name ?? '',
                    $link->title,
                    $link->url,
                    $link->wp_url,
                    $link->anchor,
                    $link->text,
                    $link->image,
                    $link->type,
                    $link->status,
                    $link->failed_reason,
                    $link->check_status,
                    $link->check_error,
                    $link->checked_at,
                ]);
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
