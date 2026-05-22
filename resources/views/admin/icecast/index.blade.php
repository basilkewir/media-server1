@extends('layouts.admin')
@section('title', 'Icecast Streams')

@section('content')
<div class="card">
    <div class="card-header">
        <span class="card-title">🎙 Icecast2 Streams</span>
        <a href="http://{{ $icecastHost }}:{{ $icecastPort }}/admin" target="_blank" class="btn btn-ghost btn-sm">Icecast Admin ↗</a>
    </div>
    <div class="hint" style="margin-bottom:1.25rem;">Server: <code class="mono">{{ $icecastHost }}:{{ $icecastPort }}</code></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Channel</th><th>Mount Point</th><th>Stream URL</th><th>Listeners</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($channels as $row)
        @php $ch = $row['channel']; @endphp
        <tr>
            <td style="font-weight:600;">{{ $ch->name }}</td>
            <td class="mono text-sm">{{ $row['mount'] ?? '—' }}</td>
            <td>
                @if($row['stream_url'])
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <code class="mono text-sm">{{ $row['stream_url'] }}</code>
                    <a href="{{ $row['stream_url'] }}" target="_blank" class="btn btn-ghost btn-sm">▶</a>
                </div>
                @else <span class="text-muted">—</span> @endif
            </td>
            <td>{{ $row['stats']['listeners'] ?? '—' }}</td>
            <td>
                @if($row['enabled']) <span class="badge badge-live">Enabled</span>
                @else <span class="badge badge-stopped">Disabled</span>
                @endif
            </td>
            <td>
                <div class="actions">
                    @if($row['enabled'])
                    <form method="POST" action="{{ route('admin.icecast.disable', $ch) }}">@csrf <button class="btn btn-danger btn-sm">Disable</button></form>
                    @else
                    <form method="POST" action="{{ route('admin.icecast.enable', $ch) }}">@csrf <button class="btn btn-success btn-sm">Enable</button></form>
                    @endif
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection
