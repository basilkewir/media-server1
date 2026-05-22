<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MediaServer\MediaServerManager;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function index(): View
    {
        $settings = [
            'driver'             => config('services.media_server.driver', 'ffmpeg'),
            'wowza_url'          => config('services.wowza.url'),
            'wowza_username'     => config('services.wowza.username'),
            'wowza_application'  => config('services.wowza.application'),
            'flussonic_url'      => config('services.flussonic.url'),
            'flussonic_username' => config('services.flussonic.username'),
            'ffmpeg_path'        => config('services.ffmpeg.path'),
            'hls_segment_duration'     => config('services.stream.hls_segment_duration'),
            'hls_segments_in_playlist' => config('services.stream.hls_segments_in_playlist'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'driver'             => 'required|in:ffmpeg,wowza,flussonic',
            'wowza_url'          => 'nullable|url',
            'wowza_username'     => 'nullable|string',
            'wowza_password'     => 'nullable|string',
            'wowza_application'  => 'nullable|string',
            'flussonic_url'      => 'nullable|url',
            'flussonic_username' => 'nullable|string',
            'flussonic_password' => 'nullable|string',
            'ffmpeg_path'        => 'nullable|string',
            'hls_segment_duration'     => 'nullable|integer|min:1|max:30',
            'hls_segments_in_playlist' => 'nullable|integer|min:2|max:60',
        ]);

        // Write to .env file
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        $map = [
            'MEDIA_SERVER_DRIVER'      => $v['driver'],
            'WOWZA_URL'                => $v['wowza_url'] ?? '',
            'WOWZA_USERNAME'           => $v['wowza_username'] ?? '',
            'WOWZA_APPLICATION'        => $v['wowza_application'] ?? 'live',
            'FLUSSONIC_URL'            => $v['flussonic_url'] ?? '',
            'FLUSSONIC_USERNAME'       => $v['flussonic_username'] ?? '',
            'FFMPEG_PATH'              => $v['ffmpeg_path'] ?? '/usr/bin/ffmpeg',
            'HLS_SEGMENT_DURATION'     => $v['hls_segment_duration'] ?? 2,
            'HLS_SEGMENTS_IN_PLAYLIST' => $v['hls_segments_in_playlist'] ?? 10,
        ];

        // Only update passwords if provided
        if (!empty($v['wowza_password'])) {
            $map['WOWZA_PASSWORD'] = $v['wowza_password'];
        }
        if (!empty($v['flussonic_password'])) {
            $map['FLUSSONIC_PASSWORD'] = $v['flussonic_password'];
        }

        foreach ($map as $key => $value) {
            if (preg_match("/^{$key}=/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);

        // Clear config cache so new values take effect
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        MediaServerManager::reset();

        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings saved. Media server driver: ' . strtoupper($v['driver']));
    }
}
