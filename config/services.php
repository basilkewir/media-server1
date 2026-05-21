<?php

return [

    'ffmpeg' => [
        'path'      => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
        'log_level' => env('FFMPEG_LOG_LEVEL', 'warning'),
    ],

    'stream' => [
        // HLS viewer latency = segment_duration × segments_in_playlist
        // 2s × 3 = ~6s viewer latency (good balance)
        // Lower = less latency but more HTTP requests from players
        'hls_segment_duration'     => env('HLS_SEGMENT_DURATION', 2),
        'hls_segments_in_playlist' => env('HLS_SEGMENTS_IN_PLAYLIST', 3),

        // Named pipe: zero-latency MPEG-TS passthrough for output targets
        // Outputs reading from the pipe have <100ms latency (network only)
        'pipe_enabled'             => env('STREAM_PIPE_ENABLED', true),

        'source_timeout'           => env('SOURCE_TIMEOUT', 5),
        'vod_fallback_enabled'     => env('VOD_FALLBACK_ENABLED', true),
        'health_check_interval'    => env('STREAM_HEALTH_CHECK_INTERVAL', 5),
    ],

    'icecast' => [
        'host'                    => env('ICECAST_HOST', 'localhost'),
        'port'                    => env('ICECAST_PORT', 8000),
        'admin_user'              => env('ICECAST_ADMIN_USER', 'admin'),
        'admin_password'          => env('ICECAST_ADMIN_PASSWORD', 'hackme'),
        'source_password'         => env('ICECAST_SOURCE_PASSWORD', 'hackme'),
        'relay_password'          => env('ICECAST_RELAY_PASSWORD', 'hackme'),
        'max_listeners_per_stream'=> env('ICECAST_MAX_LISTENERS', 1000),
        'conf_dir'                => env('ICECAST_CONF_DIR', '/etc/icecast2/mounts'),
    ],

    'rtmp' => [
        'host' => env('RTMP_HOST', 'localhost'),
        'port' => env('RTMP_PORT', 1935),
    ],

];
