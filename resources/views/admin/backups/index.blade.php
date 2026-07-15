@extends('layouts.admin')

@section('title', 'Backups')

@section('content')
    <div class="page-header mb-3">
        <div class="title-wrapper mb-2">
            <div class="col-auto d-block">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-database menu-icon"></i>
                    </span> Backups
                </h3>
            </div>

            <div class="col-auto ms-auto text-end mt-n1 d-flex gap-2">
                <form action="{{ route('admin.backups.upload') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2 ajax-import-form" data-reload="2000">
                    @csrf
                    <input type="file" name="backup_file" id="backup_file" accept=".json" class="d-none"
                           onchange="this.form.requestSubmit()">
                    <label for="backup_file" class="btn btn-outline-secondary mb-0" style="cursor:pointer">
                        <i class="mdi mdi-upload me-1"></i> Upload Backup
                    </label>
                </form>
                <form action="{{ route('admin.backups.store') }}" method="POST" class="ajax-quiet-form" data-reload="2000">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-content-save me-1"></i> Create Backup
                    </button>
                </form>
            </div>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Backups</li>
            </ol>
        </nav>
    </div>

    <div class="alert alert-info" role="alert">
        Backs up only the <strong>sites</strong> and <strong>links</strong> tables. Restoring a
        backup <strong>replaces</strong> all current sites and links with the backup's snapshot —
        this cannot be undone automatically.
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>File</th>
                                    <th>Created</th>
                                    <th>Size</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($backups as $backup)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $backup['filename'] }}</td>
                                        <td>{{ \Illuminate\Support\Carbon::createFromTimestamp($backup['last_modified'])->format('d.m.Y H:i:s') }}</td>
                                        <td>{{ number_format($backup['size'] / 1024, 1) }} KB</td>
                                        <td class="d-flex flex-row justify-content-end">
                                            <a href="{{ route('admin.backups.download', $backup['filename']) }}" class="btn btn-inverse-secondary btn-icon me-2" title="Download">
                                                <i class="mdi mdi-download"></i>
                                            </a>
                                            <form action="{{ route('admin.backups.restore', $backup['filename']) }}" method="POST"
                                                  class="ajax-confirm-form"
                                                  data-confirm="Restore {{ $backup['filename'] }}? This REPLACES all current sites and links with this backup's snapshot and cannot be undone automatically.">
                                                @csrf
                                                <button type="submit" class="btn btn-inverse-danger btn-icon me-2" title="Restore">
                                                    <i class="mdi mdi-restore"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.backups.destroy', $backup['filename']) }}" method="POST"
                                                  class="ajax-confirm-form" data-reload="2000"
                                                  data-confirm="Delete {{ $backup['filename'] }}? This cannot be undone.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-inverse-secondary btn-icon" title="Delete">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No backups yet</td>
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