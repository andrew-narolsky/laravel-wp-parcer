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
            @foreach($results->reject->isWorking() as $result)
                <tr>
                    <td>{{ $result->link->id }}</td>
                    <td>{{ $result->link->site->name }}</td>
                    <td><a href="{{ $result->link->url }}">{{ Str::limit($result->link->url, 50) }}</a></td>
                    <td>{{ $result->link->anchor }}</td>
                    <td>{{ $result->link->type === 'post' ? 'In post' : 'Homepage' }}</td>
                    <td>
                        @if($result->link->wp_url)
                            <a href="{{ $result->link->wp_url }}">{{ Str::limit($result->link->wp_url, 50) }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="reason">{{ $result->failReason() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p style="color:#27ae60; font-weight:bold">All links are working correctly.</p>
@endif

<div class="footer">Sent by {{ config('app.name') }}</div>

</body>
</html>
