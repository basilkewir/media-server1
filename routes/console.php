<?php

use Illuminate\Support\Facades\Schedule;

if (config('services.stream.vod_fallback_enabled', true)) {
    Schedule::command('stream:monitor')->everyMinute()->withoutOverlapping();
}

Schedule::command('relay:health-check')->everyFiveMinutes()->withoutOverlapping();
