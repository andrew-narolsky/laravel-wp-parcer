<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSiteRequest;
use App\Http\Requests\Admin\UpdateSiteRequest;
use App\Jobs\CheckSiteConnectionJob;
use App\Jobs\ImportSitesFromCsvJob;
use App\Jobs\ReplaceHomepageContentJob;
use App\Models\Link;
use App\Models\Project;
use App\Models\Site;
use App\Services\Publishers\HomepagePublisher;
use App\Services\WordPressXmlRpcClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SiteController extends Controller
{
    private const SORTABLE = [
        'name'               => 'name',
        'url'                => 'url',
        'is_active'          => 'is_active',
        'is_auto'            => 'is_auto',
        'posts_available'    => 'posts_available',
        'homepage_available' => 'homepage_available',
        'created_at'         => 'created_at',
    ];

    public function index(Request $request): View
    {
        $sort      = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $search    = $request->string('search')->toString();
        [$postsAvailable, $homepageAvailable, $isActive, $isAuto] = $this->resolveAvailabilityFilters($request);

        if (!array_key_exists($sort, self::SORTABLE)) {
            $sort = 'created_at';
        }

        $sites = Site::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%' . $search . '%'))
            ->when($postsAvailable !== '', fn ($query) => $this->applyAvailabilityFilter($query, 'posts_available', $postsAvailable))
            ->when($homepageAvailable !== '', fn ($query) => $this->applyAvailabilityFilter($query, 'homepage_available', $homepageAvailable))
            ->when($isActive !== '', fn ($query) => $this->applyAvailabilityFilter($query, 'is_active', $isActive))
            ->when($isAuto !== '', fn ($query) => $this->applyAvailabilityFilter($query, 'is_auto', $isAuto))
            ->orderBy(self::SORTABLE[$sort], $direction)
            ->paginate(50)
            ->withQueryString();

        $projects = Project::orderBy('name')->get();

        return view('admin.sites.index', compact('sites', 'sort', 'direction', 'search', 'postsAvailable', 'homepageAvailable', 'isActive', 'isAuto', 'projects'));
    }

    public function create(): View
    {
        return view('admin.sites.create');
    }

    public function store(StoreSiteRequest $request): RedirectResponse
    {
        $site = Site::create($request->validated());

        dispatch(new CheckSiteConnectionJob($site));

        return redirect()->route('admin.sites.index')->with('success', 'Site added');
    }

    public function edit(Site $site): View
    {
        return view('admin.sites.edit', compact('site'));
    }

    public function update(UpdateSiteRequest $request, Site $site): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active']          = $request->boolean('is_active');
        $data['is_auto']            = $this->nullableBoolFromRequest($request, 'is_auto');
        $data['posts_available']    = $this->nullableBoolFromRequest($request, 'posts_available');
        $data['homepage_available'] = $this->nullableBoolFromRequest($request, 'homepage_available');

        $site->fill($data);
        $credentialsChanged = $site->isDirty(['url', 'login', 'password']);
        $site->save();

        if ($credentialsChanged) {
            dispatch(new CheckSiteConnectionJob($site));
        }

        return redirect()->route('admin.sites.edit', $site)->with('success', 'Site updated');
    }

    public function replaceContent(Site $site): JsonResponse
    {
        dispatch(new ReplaceHomepageContentJob($site, (string) $site->homepage_content));

        return response()->json(['message' => 'Queued for content replacement.']);
    }

    public function fetchHomepageContent(Site $site, HomepagePublisher $homepagePublisher): JsonResponse
    {
        try {
            $postId = $homepagePublisher->findFrontPageId($site);

            $post = WordPressXmlRpcClient::call($site, 'wp.getPost', [
                0,
                $site->login,
                $site->password,
                $postId,
                ['post_content'],
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Could not fetch homepage content: ' . $e->getMessage()], 422);
        }

        $site->update(['homepage_content' => (string) ($post['post_content'] ?? '')]);

        return response()->json(['message' => 'Homepage content fetched from the site.']);
    }

    private function nullableBoolFromRequest(Request $request, string $field): ?bool
    {
        return match ($request->input($field)) {
            '1'     => true,
            '0'     => false,
            default => null,
        };
    }

    public function destroy(Site $site): RedirectResponse
    {
        $site->delete();

        return redirect()->route('admin.sites.index')->with('success', 'Site deleted');
    }

    public function import(Request $request): JsonResponse
    {
        return $this->handleImport($request, 'post');
    }

    public function importHomepage(Request $request): JsonResponse
    {
        return $this->handleImport($request, 'homepage');
    }

    private function handleImport(Request $request, string $linkType): JsonResponse
    {
        $request->validate([
            'csv_file'   => ['required', 'file', 'mimes:csv,txt', 'max:102400'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
        ]);

        $path = $request->file('csv_file')->store('imports');

        dispatch(new ImportSitesFromCsvJob($path, $linkType, $request->integer('project_id') ?: null));

        return response()->json(['message' => 'CSV import started. Sites will appear shortly.']);
    }

    public function refreshStatus(): JsonResponse
    {
        $upSiteIdsByType = fn (string $type) => Link::query()
            ->where('type', $type)
            ->whereIn('check_status', ['alive', 'compromised'])
            ->pluck('site_id')
            ->unique();

        $homepageUpSiteIds = $upSiteIdsByType('homepage');
        $postsUpSiteIds    = $upSiteIdsByType('post');
        $activeSiteIds     = $homepageUpSiteIds->merge($postsUpSiteIds)->unique();

        Site::query()
            ->whereIn('id', $homepageUpSiteIds)
            ->update(['homepage_available' => true]);

        Site::query()
            ->whereNotIn('id', $homepageUpSiteIds)
            ->update(['homepage_available' => false]);

        Site::query()
            ->whereIn('id', $postsUpSiteIds)
            ->update(['posts_available' => true]);

        Site::query()
            ->whereNotIn('id', $postsUpSiteIds)
            ->update(['posts_available' => false]);

        Site::query()
            ->whereIn('id', $activeSiteIds)
            ->update(['is_active' => true]);

        Site::query()
            ->whereNotIn('id', $activeSiteIds)
            ->update(['is_active' => false]);

        return response()->json(['message' => 'Site statuses updated.']);
    }

    public function export(Request $request): StreamedResponse
    {
        $search = $request->string('search')->toString();
        [$postsAvailable, $homepageAvailable, $isActive, $isAuto] = $this->resolveAvailabilityFilters($request);

        $filename = 'sites-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($search, $postsAvailable, $homepageAvailable, $isActive, $isAuto) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['site', 'login', 'password']);

            Site::query()
                ->when($search !== '', fn ($query) => $query->where('name', 'like', '%' . $search . '%'))
                ->when($postsAvailable !== '', fn ($query) => $this->applyAvailabilityFilter($query, 'posts_available', $postsAvailable))
                ->when($homepageAvailable !== '', fn ($query) => $this->applyAvailabilityFilter($query, 'homepage_available', $homepageAvailable))
                ->when($isActive !== '', fn ($query) => $this->applyAvailabilityFilter($query, 'is_active', $isActive))
                ->when($isAuto !== '', fn ($query) => $this->applyAvailabilityFilter($query, 'is_auto', $isAuto))
                ->orderBy('id')
                ->lazy(500)
                ->each(function (Site $site) use ($handle) {
                    fputcsv($handle, [$site->url, $site->login, $site->password]);
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportLinkContent(Request $request): StreamedResponse
    {
        $search = $request->string('search')->toString();
        [$postsAvailable, $homepageAvailable, $isActive, $isAuto] = $this->resolveAvailabilityFilters($request);

        $filename = 'link-content-' . now()->format('Y-m-d-His') . '.html';

        $sites = Link::query()
            ->where('type', 'homepage')
            ->whereNotNull('project_id')
            ->whereNotNull('text')
            ->where('text', '!=', '')
            ->whereHas('site', function ($query) use ($search, $postsAvailable, $homepageAvailable, $isActive, $isAuto) {
                $query
                    ->when($search !== '', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                    ->when($postsAvailable !== '', fn ($q) => $this->applyAvailabilityFilter($q, 'posts_available', $postsAvailable))
                    ->when($homepageAvailable !== '', fn ($q) => $this->applyAvailabilityFilter($q, 'homepage_available', $homepageAvailable))
                    ->when($isActive !== '', fn ($q) => $this->applyAvailabilityFilter($q, 'is_active', $isActive))
                    ->when($isAuto !== '', fn ($q) => $this->applyAvailabilityFilter($q, 'is_auto', $isAuto));
            })
            ->with(['site', 'project'])
            ->get()
            ->filter(fn (Link $link) => $link->site && $link->project)
            ->groupBy('site_id')
            ->map(function ($siteLinks) {
                $contents = $siteLinks
                    ->groupBy('project_id')
                    ->map(fn ($projectLinks) => $projectLinks->sortByDesc('id')->first()->text)
                    ->values();

                return [
                    'domain'   => $siteLinks->first()->site->name,
                    'contents' => $contents,
                ];
            })
            ->values();

        return response()->streamDownload(function () use ($sites) {
            echo $sites
                ->map(fn ($site) => '<h1>' . e($site['domain']) . "</h1>\n" . $site['contents']->implode("\n"))
                ->implode("\n---\n");
        }, $filename, ['Content-Type' => 'text/html']);
    }

    /** @return array{0: string, 1: string, 2: string, 3: string} [postsAvailable, homepageAvailable, isActive, isAuto] */
    private function resolveAvailabilityFilters(Request $request): array
    {
        $postsAvailable    = $request->string('posts_available')->toString();
        $homepageAvailable = $request->string('homepage_available')->toString();
        $isActive          = $request->string('is_active')->toString();
        $isAuto            = $request->string('is_auto')->toString();

        return [
            in_array($postsAvailable, ['yes', 'no'], true) ? $postsAvailable : '',
            in_array($homepageAvailable, ['yes', 'no'], true) ? $homepageAvailable : '',
            in_array($isActive, ['yes', 'no'], true) ? $isActive : '',
            in_array($isAuto, ['yes', 'no', 'unknown'], true) ? $isAuto : '',
        ];
    }

    private function applyAvailabilityFilter($query, string $column, string $value): void
    {
        match ($value) {
            'yes'     => $query->where($column, true),
            'no'      => $query->where($column, false),
            'unknown' => $query->whereNull($column),
        };
    }
}
