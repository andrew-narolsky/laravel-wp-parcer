@extends('layouts.admin')

@section('title', 'Projects')

@section('content')
    <div class="page-header mb-3">
        <div class="title-wrapper mb-2">
            <div class="col-auto d-block">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-folder-outline menu-icon"></i>
                    </span> Projects
                </h3>
            </div>

            <div class="col-auto ms-auto text-end mt-n1 d-flex gap-2">
                <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">Add Project</a>
            </div>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Projects</li>
            </ol>
        </nav>
    </div>

    @if(session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    @include('admin.partials.sortable-th', ['column' => 'name', 'label' => 'Name'])
                                    <th>Posts</th>
                                    <th>Homepage</th>
                                    @include('admin.partials.sortable-th', ['column' => 'created_at', 'label' => 'Added'])
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                    <tr>
                                        <td>{{ $projects->firstItem() + $loop->index }}</td>
                                        <td>{{ $project->name }}</td>
                                        <td>{{ $project->posts_count }}</td>
                                        <td>{{ $project->homepage_count }}</td>
                                        <td>{{ $project->created_at->format('d.m.Y') }}</td>
                                        <td class="d-flex flex-row justify-content-end">
                                            <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-inverse-info btn-icon me-2">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            <form action="{{ route('admin.projects.destroy', $project) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Delete project?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-inverse-danger btn-icon">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No projects yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                {{ $projects->links('admin.partials.pagination') }}
            </div>
        </div>
    </div>

@endsection
