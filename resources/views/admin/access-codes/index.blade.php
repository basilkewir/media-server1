@extends('layouts.admin')

@section('title', 'Access Codes')

@section('content')
<div class="card">
    <h1>Access Codes</h1>

    @if($codes->count())
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Type</th>
                <th>Duration</th>
                <th>Uses</th>
                <th>Status</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($codes as $code)
            <tr>
                <td><code>{{ $code->code }}</code></td>
                <td><span class="badge badge-{{ $code->type }}">{{ $code->getTypeLabel() }}</span></td>
                <td>{{ $code->duration_days }} days</td>
                <td>{{ $code->uses_count }} / {{ $code->max_uses }}</td>
                <td>
                    @if($code->isValid())
                        <span style="color: var(--success); font-weight: 600;">Active</span>
                    @else
                        <span style="color: var(--text-muted);">Inactive</span>
                    @endif
                </td>
                <td>{{ $code->created_at->format('Y-m-d') }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.access-codes.destroy', $code) }}" style="display:inline;" onsubmit="return confirm('Deactivate this code?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:0.875rem;">Deactivate</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 1rem;">
        {{ $codes->links() }}
    </div>
    @else
    <p style="color: var(--text-muted);">No access codes found. <a href="{{ route('admin.access-codes.create') }}">Generate some</a>.</p>
    @endif
</div>
@endsection
