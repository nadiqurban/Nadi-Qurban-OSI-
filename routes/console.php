<?php

use App\Models\ApiRequest;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Bayaran Ansuran: Lewat Bayar status + H-3 / H+1 reminders (Phase 5).
Schedule::command('installments:daily')->dailyAt('08:00')->timezone('Asia/Kuala_Lumpur')->withoutOverlapping();

// Kewangan: invoice status (Lewat) refresh (Phase 7).
Schedule::command('finance:daily')->dailyAt('00:15')->timezone('Asia/Kuala_Lumpur')->withoutOverlapping();

// Audit Log: keep 24 months (config activitylog.clean_after_days = 730).
Schedule::command('activitylog:clean --force')->monthlyOn(1, '03:00')->timezone('Asia/Kuala_Lumpur');

// Integrasi: prune API request + webhook delivery logs older than 90 days.
Schedule::command('model:prune', ['--model' => [ApiRequest::class, WebhookDelivery::class]])->dailyAt('02:30')->timezone('Asia/Kuala_Lumpur');
