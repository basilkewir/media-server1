<nav>
    <a href="{{ route('client.dashboard') }}" class="nav-item {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Dashboard
    </a>
    <a href="{{ route('client.library') }}" class="nav-item {{ request()->routeIs('client.library') ? 'active' : '' }}">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
        Library
    </a>
    <a href="{{ route('client.streams') }}" class="nav-item {{ request()->routeIs('client.streams') ? 'active' : '' }}">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="23 7 16 12 7 21 1 14 8 9 1 3"/></svg>
        Live Streams
    </a>

    @php $sub = $clientSubscription ?? null; @endphp
    @if($sub && in_array($sub['type'] ?? '', ['premium']))
    <a href="{{ route('client.premium') }}" class="nav-item {{ request()->routeIs('client.premium') ? 'active' : '' }}">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        Premium
    </a>
    @endif

    {{-- Conditional menu items based on subscription --}}
    @if(!$sub)
    <div style="margin-top:16px;padding:10px 12px;background:var(--surface-2);border-radius:var(--radius-sm);font-size:12px;color:var(--text-tertiary);">
        <div style="font-weight:600;color:var(--warning);margin-bottom:4px;">No Access</div>
        Redeem an access code to unlock features.
    </div>
    @endif
</nav>
