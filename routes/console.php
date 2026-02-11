<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Clone production DB to staging every 30 minutes (ensure queue worker and cron are running)
Schedule::command('db:clone-to-staging')->everyThirtyMinutes();
