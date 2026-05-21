@php
$type = $clientSubscription['type'] ?? null;
$isGuest = is_null($type);
$isLibrary = in_array($type, ['library_only', 'full_access', 'premium']);
$isFull = in_array($type, ['full_access', 'premium']);
$isPremium = $type === 'premium';
@endphp

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h1>MediaServer</h1>
        @if($isGuest)
            <span class="badge badge-guest">Guest</span>
        @else
            <span class="badge badge-{{ str_replace('_', '-', $type) }}">{{ $clientSubscription['type_label'] ?? 'Subscriber' }}</span>
            @if(isset($clientSubscription['days_remaining']))
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                    {{ $clientSubscription['days_remaining'] }} days left
                </div>
            @endif
        @endif
    </div>

    <nav class="sidebar-nav">
        {{-- Everyone sees Dashboard --}}
        <div class="nav-section">
            <div class="nav-section-title">Menu</div>
            <a href="{{ route('client.dashboard') }}" class="nav-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
        </div>

        {{-- Library: visible to all subscribers --}}
        @if($isLibrary)
        <div class="nav-section">
            <div class="nav-section-title">Content</div>
            <a href="{{ route('client.library') }}" class="nav-link {{ request()->routeIs('client.library') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Library
            </a>
        </div>
        @endif

        {{-- Live Streams: visible to Full Access & Premium only --}}
        @if($isFull)
        <div class="nav-section">
            <div class="nav-section-title">Live</div>
            <a href="{{ route('client.streams') }}" class="nav-link {{ request()->routeIs('client.streams') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Live Streams
            </a>
        </div>
        @endif

        {{-- Premium: visible to Premium only --}}
        @if($isPremium)
        <div class="nav-section">
            <div class="nav-section-title">Premium</div>
            <a href="{{ route('client.premium') }}" class="nav-link {{ request()->routeIs('client.premium') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                Premium Content
            </a>
        </div>
        @endif

        {{-- Guest: show Enter Code link --}}
        @if($isGuest)
        <div class="nav-section">
            <div class="nav-section-title">Access</div>
            <a href="{{ route('stream.play', ['slug' => 'main']) }}" class="nav-link">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                Enter Access Code
            </a>
        </div>
        @endif
    </nav>

    <div class="sidebar-footer">
        MediaServer v{{ config('app.version', '1.1.0') }}
    </div>
</aside>
