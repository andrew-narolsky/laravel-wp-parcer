@extends('layouts.admin')

@section('title', 'Sites')

@section('content')
    <div class="page-header mb-3">
        <div class="title-wrapper mb-2">
            <div class="col-auto d-block">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-web menu-icon"></i>
                    </span> Countries
                </h3>
            </div>

            <div class="col-auto ms-auto text-end mt-n1">
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
                                        <td>{{ $site->id }}</td>
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
            </div>
        </div>
    </div>

@endsection
