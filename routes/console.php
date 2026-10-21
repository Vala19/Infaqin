<?php

use App\Models\Campaign;
use App\Models\Donation;
use App\Services\DonationStatusUpdater;
use App\Services\MidtransClient;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/**
 * Backup for the webhook: a donor who closes Snap without choosing a method never gets an "expire"
 * notification. Midtrans is always asked first; only a missing transaction (404) is expired directly.
 */
Artisan::command('donations:expire', function (MidtransClient $midtrans, DonationStatusUpdater $updater) {
    $counts = array_fill_keys(Donation::STATUSES, 0) + ['skipped' => 0];

    Donation::expired()->chunkById(100, function ($donations) use ($midtrans, $updater, &$counts) {
        foreach ($donations as $donation) {
            try {
                $transaction = $midtrans->status($donation->reference);
            } catch (Throwable $e) {
                report($e);
                $this->warn("Could not check {$donation->reference}; it will be retried on the next run.");
                $counts['skipped']++;

                continue;
            }

            $donation = $transaction ? $updater->apply($donation, $transaction) : $updater->expireMissing($donation);
            $counts[$donation->status]++;
        }
    });

    $this->info(sprintf(
        'Checked overdue donations: %d expired, %d paid, %d failed, %d still pending, %d skipped.',
        $counts[Donation::STATUS_EXPIRED], $counts[Donation::STATUS_PAID], $counts[Donation::STATUS_FAILED],
        $counts[Donation::STATUS_PENDING], $counts['skipped'],
    ));
})->purpose('Check overdue pending donations with Midtrans and apply their final status');

Schedule::command('donations:expire')->hourly()->withoutOverlapping();

Artisan::command('campaigns:close', function () {
    $closed = Campaign::pastDeadline()->update(['status' => Campaign::STATUS_CLOSED]);

    $this->info("Closed {$closed} campaign(s) past their deadline.");
})->purpose('Close active campaigns whose deadline has passed');

Schedule::command('campaigns:close')->daily()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
