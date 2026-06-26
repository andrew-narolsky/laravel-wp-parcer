@props([
    'name',
    'options',
    'selected' => '',
    'placeholder' => '— Select —',
])

@php $uid = 'ss_' . $name; @endphp

<input type="hidden" name="{{ $name }}" id="{{ $uid }}_input" value="{{ $selected }}">
<div class="site-select-wrapper">
    <input type="text" id="{{ $uid }}_search" autocomplete="off"
           class="form-control {{ $errors->has($name) ? 'is-invalid' : '' }}"
           placeholder="{{ $placeholder }}">
    <div class="site-select-dropdown" id="{{ $uid }}_dropdown">
        @foreach($options as $value => $label)
            <div class="site-select-option {{ (string) $selected === (string) $value ? 'selected' : '' }}"
                 data-id="{{ $value }}"
                 data-label="{{ $label }}">
                {{ $label }}
            </div>
        @endforeach
        <div class="site-select-option no-results hidden">No results</div>
    </div>
</div>

@once
    @push('styles')
    <style>
        .site-select-wrapper { position: relative; }
        .site-select-dropdown {
            position: absolute; top: 100%; left: 0; right: 0; z-index: 1000;
            background: #fff; border: 1px solid #ced4da; border-radius: 0 0 .375rem .375rem;
            max-height: 240px; overflow-y: auto; display: none;
        }
        .site-select-dropdown.show { display: block; }
        .site-select-option {
            padding: .4rem .75rem; cursor: pointer; font-size: .875rem;
        }
        .site-select-option:hover, .site-select-option.active { background: #e9ecef; }
        .site-select-option.selected { background: #0d6efd; color: #fff; }
        .site-select-option.selected:hover { background: #0b5ed7; }
        .site-select-option.hidden { display: none; }
        .site-select-option.no-results { color: #6c757d; cursor: default; }
        .site-select-option.no-results:hover { background: none; }
    </style>
    @endpush

    @push('js')
    <script>
        function initSearchableSelect(uid) {
            const search   = document.getElementById(uid + '_search');
            const hidden   = document.getElementById(uid + '_input');
            const dropdown = document.getElementById(uid + '_dropdown');
            const options  = Array.from(dropdown.querySelectorAll('.site-select-option:not(.no-results)'));
            const noResults = dropdown.querySelector('.no-results');

            const selected = options.find(o => o.classList.contains('selected'));
            if (selected) search.value = selected.dataset.label;

            function openDropdown() { dropdown.classList.add('show'); }
            function closeDropdown() { dropdown.classList.remove('show'); }

            function filterOptions(q) {
                const query = q.toLowerCase();
                let visible = 0;
                options.forEach(o => {
                    const match = o.dataset.label.toLowerCase().includes(query);
                    o.classList.toggle('hidden', !match);
                    if (match) visible++;
                });
                noResults.classList.toggle('hidden', visible > 0);
            }

            function selectOption(opt) {
                options.forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected');
                hidden.value = opt.dataset.id;
                search.value = opt.dataset.label;
                closeDropdown();
            }

            search.addEventListener('focus', () => { filterOptions(search.value); openDropdown(); });
            search.addEventListener('input', () => { hidden.value = ''; filterOptions(search.value); openDropdown(); });

            dropdown.addEventListener('mousedown', e => {
                const opt = e.target.closest('.site-select-option:not(.no-results)');
                if (opt) selectOption(opt);
            });

            document.addEventListener('click', e => {
                if (!search.contains(e.target) && !dropdown.contains(e.target)) closeDropdown();
            });
        }
    </script>
    @endpush
@endonce

@push('js')
<script>initSearchableSelect('{{ $uid }}');</script>
@endpush