<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSiteRequest;
use App\Http\Requests\Admin\UpdateSiteRequest;
use App\Jobs\CheckSiteConnectionJob;
use App\Jobs\ImportSitesFromCsvJob;
use App\Models\Project;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteController extends Controller
{
    private const SORTABLE = [
        'name'               => 'name',
        'url'                => 'url',
        'is_active'          => 'is_active',
        'posts_available'    => 'posts_available',
        'homepage_available' => 'homepage_available',
        'created_at'         => 'created_at',
    ];

    public function index(Request $request): View
    {
        $sort      = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        [$postsAvailable, $homepageAvailable] = $this->resolveAvailabilityFilters($request);

        if (!array_key_exists($sort, self::SORTABLE)) {
            $sort = 'created_at';
        }

        $sites = Site::query()
            ->when($postsAvailable !== '', fn ($query) => $this->applyAvailabilityFilter($query, 'posts_available', $postsAvailable))
            ->when($homepageAvailable !== '', fn ($query) => $this->applyAvailabilityFilter($query, 'homepage_available', $homepageAvailable))
            ->orderBy(self::SORTABLE[$sort], $direction)
            ->paginate(50)
            ->withQueryString();

        $projects = Project::orderBy('name')->get();

        return view('admin.sites.index', compact('sites', 'sort', 'direction', 'postsAvailable', 'homepageAvailable', 'projects'));
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
        $data['posts_available']    = $this->nullableBoolFromRequest($request, 'posts_available');
        $data['homepage_available'] = $this->nullableBoolFromRequest($request, 'homepage_available');

        $site->fill($data);
        $credentialsChanged = $site->isDirty(['url', 'login', 'password']);
        $site->save();

        if ($credentialsChanged) {
            dispatch(new CheckSiteConnectionJob($site));
        }

        return redirect()->route('admin.sites.index')->with('success', 'Site updated');
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

    public function export(Request $request): StreamedResponse
    {
        [$postsAvailable, $homepageAvailable] = $this->resolveAvailabilityFilters($request);

        $filename = 'sites-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($postsAvailable, $homepageAvailable) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['site', 'login', 'password']);

            Site::query()
                ->when($postsAvailable !== '', fn ($query) => $this->applyAvailabilityFilter($query, 'posts_available', $postsAvailable))
                ->when($homepageAvailable !== '', fn ($query) => $this->applyAvailabilityFilter($query, 'homepage_available', $homepageAvailable))
                ->orderBy('id')
                ->lazy(500)
                ->each(function (Site $site) use ($handle) {
                    fputcsv($handle, [$site->url, $site->login, $site->password]);
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** @return array{0: string, 1: string} [postsAvailable, homepageAvailable] */
    private function resolveAvailabilityFilters(Request $request): array
    {
        $postsAvailable    = $request->string('posts_available')->toString();
        $homepageAvailable = $request->string('homepage_available')->toString();

        return [
            in_array($postsAvailable, ['yes', 'no'], true) ? $postsAvailable : '',
            in_array($homepageAvailable, ['yes', 'no'], true) ? $homepageAvailable : '',
        ];
    }

    private function applyAvailabilityFilter($query, string $column, string $value): void
    {
        match ($value) {
            'yes' => $query->where($column, true),
            'no'  => $query->where($column, false),
        };
    }
}
