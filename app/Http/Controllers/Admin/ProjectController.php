<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProjectRequest;
use App\Http\Requests\Admin\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    private const SORTABLE = [
        'name'       => 'name',
        'created_at' => 'created_at',
    ];

    public function index(Request $request): View
    {
        $sort      = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        if (!array_key_exists($sort, self::SORTABLE)) {
            $sort = 'name';
        }

        $projects = Project::query()
            ->withCount([
                'links as posts_count'    => fn ($query) => $query->where('type', 'post'),
                'links as homepage_count' => fn ($query) => $query->where('type', 'homepage'),
            ])
            ->orderBy(self::SORTABLE[$sort], $direction)
            ->paginate(50)
            ->withQueryString();

        return view('admin.projects.index', compact('projects', 'sort', 'direction'));
    }

    public function create(): View
    {
        return view('admin.projects.create');
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        Project::create($request->validated());

        return redirect()->route('admin.projects.index')->with('success', 'Project added');
    }

    public function edit(Project $project): View
    {
        return view('admin.projects.edit', compact('project'));
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()->route('admin.projects.index')->with('success', 'Project updated');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', 'Project deleted');
    }
}
