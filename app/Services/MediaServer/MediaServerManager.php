<?php

namespace App\Services\MediaServer;

use App\Services\ProtocolDetector;
use Illuminate\Support\Facades\App;

class MediaServerManager
{
    private static ?MediaServerDriver $instance = null;

    public static function driver(): MediaServerDriver
    {
        if (self::$instance) return self::$instance;

        $driver = config('services.media_server.driver', 'ffmpeg');

        self::$instance = match ($driver) {
            'wowza'    => App::make(WowzaDriver::class),
            'flussonic' => App::make(FlussonicDriver::class),
            default    => new FFmpegDriver(App::make(ProtocolDetector::class)),
        };

        return self::$instance;
    }

    /** Reset cached instance (useful for testing or driver switching) */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
