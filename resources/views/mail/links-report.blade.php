<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; margin: 0; padding: 20px; }
        h2 { color: #2c3e50; margin-bottom: 4px; }
        .summary { display: flex; gap: 24px; margin: 20px 0; }
        .stat { background: #f4f4f4; border-radius: 6px; padding: 14px 24px; text-align: center; }
        .stat .num { font-size: 28px; font-weight: bold; }
        .stat.green .num { color: #27ae60; }
        .stat.red .num { color: #e74c3c; }
        .stat.orange .num { color: #e67e22; }
        .stat .label { font-size: 12px; color: #666; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
        th { background: #2c3e50; color: #fff; padding: 8px 10px; text-align: left; }
        td { padding: 7px 10px; border-bottom: 1px solid #eee; vertical-align: top; }
        tr:nth-child(even) td { background: #fafafa; }
        .reason { color: #e74c3c; }
        a { color: #2980b9; text-decoration: none; }
        .footer { margin-top: 30px; font-size: 12px; color: #999; }
    </style>
</head>
<body>

@php
    $total       = $links->count();
    $working     = $links->where('check_status', 'alive')->count();
    $broken      = $links->where('check_status', 'not_found')->count();
    $blocked     = $links->where('check_status', 'blocked')->count();
    $brokenLinks = $links->where('check_status', 'not_found');
    $blockedLinks = $links->where('check_status', 'blocked');
@endphp

<h2>Links Analysis Report</h2>
<p style="color:#666">Generated automatically. Analyzed all published links.</p>

<div class="summary">
    <div class="stat">
        <div class="num">{{ $total }}</div>
        <div class="label">Total</div>
    </div>
    <div class="stat green">
        <div class="num">{{ $working }}</div>
        <div class="label">Working</div>
    </div>
    <div class="stat red">
        <div class="num">{{ $broken }}</div>
        <div class="label">Broken</div>
    </div>
    <div class="stat orange">
        <div class="num">{{ $blocked }}</div>
        <div class="label">Could not verify</div>
    </div>
</div>

@if($broken > 0)
    <h3 style="color:#e74c3c">Broken links ({{ $broken }})</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Site</th>
                <th>Target URL</th>
                <th>Anchor</th>
                <th>Type</th>
                <th>Published URL</th>
                <th>Problem</th>
            </tr>
        </thead>
        <tbody>
            @foreach($brokenLinks as $link)
                <tr>
                    <td>{{ $link->id }}</td>
                    <td>{{ $link->site->name }}</td>
                    <td><a href="{{ $link->url }}">{{ Str::limit($link->url, 50) }}</a></td>
                    <td>{{ $link->anchor }}</td>
                    <td>{{ $link->type === 'post' ? 'In post' : 'Homepage' }}</td>
                    <td>
                        @if($link->wp_url)
                            <a href="{{ $link->wp_url }}">{{ Str::limit($link->wp_url, 50) }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="reason">{{ $link->check_error }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p style="color:#27ae60; font-weight:bold">All links are working correctly.</p>
@endif

@if($blocked > 0)
    <h3 style="color:#e67e22">Could not verify — blocked by anti-bot protection ({{ $blocked }})</h3>
    <p style="color:#666">These sites blocked our automated check. Please verify manually before treating them as broken.</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Site</th>
                <th>Target URL</th>
                <th>Anchor</th>
                <th>Type</th>
                <th>Published URL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($blockedLinks as $link)
                <tr>
                    <td>{{ $link->id }}</td>
                    <td>{{ $link->site->name }}</td>
                    <td><a href="{{ $link->url }}">{{ Str::limit($link->url, 50) }}</a></td>
                    <td>{{ $link->anchor }}</td>
                    <td>{{ $link->type === 'post' ? 'In post' : 'Homepage' }}</td>
                    <td>
                        @if($link->wp_url)
                            <a href="{{ $link->wp_url }}">{{ Str::limit($link->wp_url, 50) }}</a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="footer">Sent by {{ config('app.name') }}</div>

</body>
</html>
