@extends('layouts.admin')

@section('title', 'Sites')

@section('content')
    <div class="page-header mb-3">
        <div class="title-wrapper mb-2">
            <div class="col-auto d-block">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-web menu-icon"></i>
                    </span> Sites
                </h3>
            </div>

            <div class="col-auto ms-auto text-end mt-n1 d-flex gap-2">
                <form action="{{ route('admin.sites.import') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                    @csrf
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" class="d-none"
                           onchange="this.form.submit()">
                    <label for="csv_file" class="btn btn-outline-secondary mb-0" style="cursor:pointer">
                        <i class="mdi mdi-upload me-1"></i> Import Posts
                    </label>
                </form>
                <form action="{{ route('admin.sites.import_homepage') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                    @csrf
                    <input type="file" name="csv_file" id="csv_file_homepage" accept=".csv" class="d-none"
                           onchange="this.form.submit()">
                    <label for="csv_file_homepage" class="btn btn-outline-secondary mb-0" style="cursor:pointer">
                        <i class="mdi mdi-upload me-1"></i> Import Homepage
                    </label>
                </form>
                <a href="{{ route('admin.sites.create') }}" class="btn btn-primary">Add Site</a>
            </div>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Sites</li>
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
                                    <th>Name</th>
                                    <th>URL</th>
                                    <th>Login</th>
                                    <th>Status</th>
                                    <th>Added</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sites as $site)
                                    <tr>
                                        <td>{{ $sites->firstItem() + $loop->index }}</td>
                                        <td>{{ $site->name }}</td>
                                        <td>
                                            <a href="{{ $site->url }}" target="_blank">
                                                {{ $site->url }}
                                            </a>
                                        </td>
                                        <td>{{ $site->login }}</td>
                                        <td>
                                            @if($site->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $site->created_at->format('d.m.Y') }}</td>

                                        <td class="d-flex flex-row justify-content-end">
                                            <a href="{{ route('admin.sites.edit', $site) }}" type="button" class="btn btn-inverse-info btn-icon me-2">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>

                                            <form action="{{ route('admin.sites.destroy', $site) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Delete site?')">
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
                                        <td colspan="7" class="text-center text-muted py-4">No sites yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                {{ $sites->links('admin.partials.pagination') }}
            </div>
        </div>
    </div>

@endsection
