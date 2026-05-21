<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('stream:monitor')->everyMinute()->withoutOverlapping();
Schedule::command('relay:health-check')->everyFiveMinutes()->withoutOverlapping();
