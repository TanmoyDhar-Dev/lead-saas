<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Renew Microsoft Graph mail webhook subscriptions twice daily.
Schedule::command('graph:renew-subscriptions')->twiceDaily(1, 13);
