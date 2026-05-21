<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use App\Observers\ChannelObserver;
use App\Observers\StreamObserver;
use App\Observers\OutputTargetObserver;
use App\Models\Channel;
use App\Models\Stream;
use App\Models\OutputTarget;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->bootRateLimiters();
        $this->bootValidators();
        $this->bootObservers();
    }

    private function bootRateLimiters(): void
    {
        RateLimiter::for('api', function ($request) {
            $token = $request->attributes->get('api_token_name');
            return $token
                ? Limit::perMinute(120)->by('token:' . $token)
                : Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('api_strict', function ($request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('stream_start', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }

    private function bootValidators(): void
    {
        Validator::extend('stream_url', function ($attribute, $value) {
            if (!is_string($value)) return false;
            $allowed = ['rtmp://', 'rtmps://', 'rtsp://', 'srt://', 'udp://', 'rtp://', 'tcp://', 'http://', 'https://', 'file://', '/'];
            foreach ($allowed as $prefix) {
                if (str_starts_with(strtolower($value), $prefix)) return true;
            }
            return false;
        }, 'The :attribute must be a valid streaming URL.');

        Validator::extend('slug_format', function ($attribute, $value) {
            return is_string($value) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value);
        }, 'The :attribute must be a valid slug (lowercase letters, numbers, and hyphens only).');
    }

    private function bootObservers(): void
    {
        Channel::observe(ChannelObserver::class);
        Stream::observe(StreamObserver::class);
        OutputTarget::observe(OutputTargetObserver::class);
    }
}
