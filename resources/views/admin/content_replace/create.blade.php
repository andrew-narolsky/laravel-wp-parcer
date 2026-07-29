@extends('layouts.admin')

@section('title', 'Replace Content')

@section('styles')
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
@endsection

@section('content')
    <div class="page-header mb-3">
        <div class="title-wrapper mb-2">
            <div class="col-auto d-block">
                <h3 class="page-title">
                    <span class="page-title-icon bg-gradient-primary text-white me-2">
                        <i class="mdi mdi-file-replace-outline menu-icon"></i>
                    </span> Replace content
                </h3>
            </div>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Replace content</li>
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
                    <form action="{{ route('admin.content_replace.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Site <span class="text-danger">*</span></label>
                            <x-searchable-select name="site_id"
                                :options="$sites->pluck('name', 'id')"
                                :selected="old('site_id')"
                                placeholder="— Search site —" />
                            @error('site_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Selecting a site loads its currently known homepage content below, so you're editing from the real thing instead of starting blank.</div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Content <span class="text-danger">*</span></label>
                                <button type="button" id="toggle-code-view" class="btn btn-sm btn-outline-secondary">
                                    <i class="mdi mdi-code-tags"></i> View code
                                </button>
                            </div>
                            <input type="hidden" name="text" id="text-input">
                            <div id="quill-wrapper">
                                <div id="quill-editor" style="height: 320px;"></div>
                            </div>
                            <textarea id="html-source" class="form-control d-none" rows="14" style="font-family: monospace; font-size: 0.85rem;"></textarea>
                            @error('text')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div class="form-text">This fully overwrites the homepage content on the selected site.</div>
                        </div>

                        <button type="submit" class="btn btn-gradient-primary">
                            <i class="mdi mdi-content-save"></i> Replace content
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
        const siteContent = {!! json_encode($sites->pluck('homepage_content', 'id')) !!};

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

        const initialText = {!! json_encode(old('text', '')) !!};
        quill.clipboard.dangerouslyPasteHTML(initialText);
        document.getElementById('text-input').value = initialText;

        quill.on('text-change', function () {
            document.getElementById('text-input').value = quill.root.innerHTML;
        });

        const codeToggleBtn = document.getElementById('toggle-code-view');
        const htmlSource = document.getElementById('html-source');
        const quillWrapper = document.getElementById('quill-wrapper');
        const textInput = document.getElementById('text-input');
        let codeViewActive = false;

        codeToggleBtn.addEventListener('click', function () {
            if (!codeViewActive) {
                htmlSource.value = textInput.value;
                quillWrapper.classList.add('d-none');
                htmlSource.classList.remove('d-none');
                codeToggleBtn.innerHTML = '<i class="mdi mdi-eye-outline"></i> Visual editor';
            } else {
                textInput.value = htmlSource.value;
                quill.setContents(quill.clipboard.convert(htmlSource.value));
                quillWrapper.classList.remove('d-none');
                htmlSource.classList.add('d-none');
                codeToggleBtn.innerHTML = '<i class="mdi mdi-code-tags"></i> View code';
            }
            codeViewActive = !codeViewActive;
        });

        htmlSource.addEventListener('input', function () {
            textInput.value = htmlSource.value;
        });

        document.getElementById('ss_site_id_dropdown').addEventListener('mousedown', function (e) {
            const option = e.target.closest('.site-select-option:not(.no-results)');
            if (!option) return;

            const content = siteContent[option.dataset.id] || '';
            textInput.value = content;

            if (codeViewActive) {
                htmlSource.value = content;
            } else {
                quill.setContents(quill.clipboard.convert(content));
            }
        });
    </script>
@endsection
