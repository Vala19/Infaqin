<?php

use App\Models\Donation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('donations:expire', function () {
    $count = Donation::expired()->update(['status' => Donation::STATUS_FAILED, 'updated_at' => now()]);
    $this->info("Expired {$count} pending donation(s).");
})->purpose('Cancel pending donations older than the payment window');

Schedule::command('donations:expire')->hourly();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
