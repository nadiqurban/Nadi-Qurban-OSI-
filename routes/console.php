<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Bayaran Ansuran: Lewat Bayar status + H-3 / H+1 reminders (Phase 5).
Schedule::command('installments:daily')->dailyAt('08:00')->timezone('Asia/Kuala_Lumpur')->withoutOverlapping();
