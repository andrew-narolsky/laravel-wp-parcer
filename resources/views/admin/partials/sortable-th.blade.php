@php
    $nextDirection = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
    $query = array_merge(request()->except(['sort', 'direction', 'page']), ['sort' => $column, 'direction' => $nextDirection]);
@endphp
<th>
    <a href="{{ request()->url() . '?' . http_build_query($query) }}" class="text-reset text-decoration-none d-inline-flex align-items-center gap-1">
        {{ $label }}
        @if($sort === $column)
            <i class="mdi mdi-arrow-{{ $direction === 'asc' ? 'up' : 'down' }}"></i>
        @endif
    </a>
</th>