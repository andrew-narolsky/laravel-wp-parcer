@extends('layouts.admin')

@section('title', 'Edit Link')

@section('styles')
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
@endsection

@section('content')
    <div class="page-header mb-3">
        <div class="title-wrapper mb-2">
            <div class="col-auto d-block">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-link menu-icon"></i>
                    </span> Edit link
                </h3>
            </div>
            <div class="col-auto ms-auto text-end mt-n1">
                <form action="{{ route('admin.links.publish', $link) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-publish"></i> Publish to site
                    </button>
                </form>
            </div>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.links.index') }}">Links</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($link->anchor, 40) }}</li>
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
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.links.update', $link) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Site <span class="text-danger">*</span></label>
                            <select name="site_id" class="form-select @error('site_id') is-invalid @enderror">
                                <option value="">— Select site —</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" @selected(old('site_id', $link->site_id) == $site->id)>
                                        {{ $site->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('site_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title', $link->title) }}" placeholder="Post title">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Link URL <span class="text-danger">*</span></label>
                            <input type="url" name="url" class="form-control @error('url') is-invalid @enderror"
                                   value="{{ old('url', $link->url) }}" placeholder="https://example.com/page">
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Anchor <span class="text-danger">*</span></label>
                            <input type="text" name="anchor" class="form-control @error('anchor') is-invalid @enderror"
                                   value="{{ old('anchor', $link->anchor) }}" placeholder="Click here">
                            @error('anchor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select @error('type') is-invalid @enderror">
                                <option value="">— Select type —</option>
                                <option value="post" @selected(old('type', $link->type) === 'post')>In post</option>
                                <option value="homepage" @selected(old('type', $link->type) === 'homepage')>Homepage</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Article text <span class="text-danger">*</span></label>
                            <input type="hidden" name="text" id="text-input">
                            <div id="quill-editor" style="height: 320px;"></div>
                            @error('text')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-gradient-primary">
                            <i class="mdi mdi-content-save"></i> Save
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
        const quill = new Quill('#quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'header': [2, 3, false] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['link'],
                    ['clean'],
                ],
            },
        });

        const initialText = {!! json_encode(old('text', $link->text)) !!};
        quill.root.innerHTML = initialText;
        document.getElementById('text-input').value = initialText;

        quill.on('text-change', function () {
            document.getElementById('text-input').value = quill.root.innerHTML;
        });
    </script>
@endsection
