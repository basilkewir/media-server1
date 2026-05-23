@extends('layouts.admin')
@section('title', 'VOD Schedule — ' . $channel->name)
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> &rsaquo;
    <a href="{{ route('admin.channels.index') }}">Channels</a> &rsaquo;
    <a href="{{ route('admin.channels.show', $channel) }}">{{ $channel->name }}</a> &rsaquo;
    <a href="{{ route('admin.vod.index', $channel) }}">VOD Library</a> &rsaquo; Schedule
@endsection

@section('content')

{{-- Add Schedule --}}
<div class="card">
    <div class="card-header"><div class="card-title">+ Add Schedule Entry</div></div>
    <form method="POST" action="{{ route('admin.vod-schedules.store', $channel) }}">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label>Video</label>
                <select name="vod_file_id" required>
                    <option value="">Select a video...</option>
                    @foreach($vodFiles as $file)
                        <option value="{{ $file->id }}">{{ $file->title }} ({{ $file->formattedDuration() }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Title Override</label>
                <input name="title" placeholder="Optional">
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Play At</label>
                <input type="datetime-local" name="play_at" required>
            </div>
            <div class="form-group">
                <label>End At (optional)</label>
                <input type="datetime-local" name="end_at">
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="toggle-label">
                    <input type="checkbox" name="is_repeating" value="1"> Repeat Weekly
                </label>
            </div>
            <div class="form-group">
                <label>Repeat Days</label>
                <div class="flex" style="flex-wrap:wrap;gap:0.5rem;">
                    @foreach([1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'] as $d => $name)
                    <label class="toggle-label">
                        <input type="checkbox" name="repeat_days[]" value="{{ $d }}"> {{ $name }}
                    </label>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="toggle-label">
                <input type="checkbox" name="override_default_playlist" value="1"> Override default playlist when active
            </label>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Add Schedule</button>
    </form>
</div>

{{-- Scheduled Entries --}}
<div class="card">
    <div class="card-header"><div class="card-title">Scheduled Playlist</div></div>

    @if($schedules->isEmpty())
        <div class="empty"><div class="empty-icon">📅</div><p>No schedules created yet.</p></div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Video</th>
                        <th>Play At</th>
                        <th>End At</th>
                        <th>Repeat</th>
                        <th>Override</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schedules as $schedule)
                    @php $isNow = $schedule->isPlayingNow(); @endphp
                    <tr style="{{ $isNow ? 'background:rgba(34,197,94,0.08);' : '' }}">
                        <td>
                            <strong>{{ $schedule->title ?: $schedule->vodFile?->title }}</strong>
                            <div class="text-xs text-muted">{{ $schedule->vodFile?->formattedDuration() }}</div>
                        </td>
                        <td>{{ $schedule->play_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $schedule->end_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>
                            @if($schedule->is_repeating)
                                <span class="badge badge-info">{{ $schedule->dayNames() }}</span>
                            @else
                                <span class="text-muted">Once</span>
                            @endif
                        </td>
                        <td>
                            @if($schedule->override_default_playlist)
                                <span class="badge badge-warning">Override</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($schedule->is_active)
                                @if($isNow)
                                    <span class="badge badge-live">● Playing</span>
                                @else
                                    <span class="badge badge-active">Active</span>
                                @endif
                            @else
                                <span class="badge badge-stopped">Disabled</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <form method="POST" action="{{ route('admin.vod-schedules.toggle', [$channel, $schedule]) }}" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-ghost btn-xs">{{ $schedule->is_active ? 'Disable' : 'Enable' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.vod-schedules.destroy', [$channel, $schedule]) }}" onsubmit="return confirm('Delete?')" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-xs" style="color:var(--danger);">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
