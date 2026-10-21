<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Donation;
use App\Notifications\DonationReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * The only code that writes donations.status (CLAUDE.md rule 3). Used by the Midtrans webhook,
 * the `donations:expire` command and donor cancellation. Every method locks the row and only
 * touches donations that are still pending, so late or repeated calls are no-ops.
 */
class DonationStatusUpdater
{
    /**
     * Maps a Midtrans transaction_status (+ fraud_status) to a donation status; null means "stay pending".
     */
    public static function map(string $transactionStatus, ?string $fraudStatus = null): ?string
    {
        return match ($transactionStatus) {
            'settlement' => Donation::STATUS_PAID,
            'capture' => match ($fraudStatus) {
                'accept' => Donation::STATUS_PAID,
                'deny' => Donation::STATUS_FAILED,
                default => null, // challenge: wait for Midtrans to send settlement or deny
            },
            'expire' => Donation::STATUS_EXPIRED,
            'deny', 'cancel', 'failure' => Donation::STATUS_FAILED,
            default => null,
        };
    }

    /**
     * Maps a Midtrans payment_type to a Donation::PAYMENT_METHODS key.
     */
    public static function paymentMethod(array $transaction): ?string
    {
        $type = $transaction['payment_type'] ?? null;

        return match (true) {
            ! is_string($type) || $type === '' => null,
            $type === 'bank_transfer' && isset($transaction['permata_va_number']) => 'permata_va',
            $type === 'bank_transfer' && isset($transaction['va_numbers'][0]['bank']) => strtolower($transaction['va_numbers'][0]['bank']).'_va',
            $type === 'echannel' => 'mandiri_va',
            default => substr($type, 0, 30),
        };
    }

    /**
     * Applies a Midtrans notification or status payload to the donation.
     */
    public function apply(Donation $donation, array $transaction): Donation
    {
        $status = self::map((string) ($transaction['transaction_status'] ?? ''), $transaction['fraud_status'] ?? null);

        return $this->transition($donation, $status, self::paymentMethod($transaction));
    }

    /**
     * Midtrans has no transaction for this order (the donor closed Snap without choosing a method).
     */
    public function expireMissing(Donation $donation): Donation
    {
        return $this->transition($donation, Donation::STATUS_EXPIRED);
    }

    public function cancelByDonor(Donation $donation): Donation
    {
        return $this->transition($donation, Donation::STATUS_FAILED);
    }

    private function transition(Donation $donation, ?string $status, ?string $paymentMethod = null): Donation
    {
        $receipt = null;

        $donation = DB::transaction(function () use ($donation, $status, $paymentMethod, &$receipt) {
            $donation = Donation::whereKey($donation->id)->lockForUpdate()->firstOrFail();

            if ($donation->status !== Donation::STATUS_PENDING) {
                return $donation;
            }

            if ($paymentMethod) {
                $donation->payment_method = $paymentMethod;
            }

            if ($status === Donation::STATUS_PAID) {
                $isNewDonor = ! Donation::where('campaign_id', $donation->campaign_id)
                    ->where('email', $donation->email)
                    ->where('status', Donation::STATUS_PAID)
                    ->exists();

                $donation->paid_at = now();

                $campaign = Campaign::whereKey($donation->campaign_id)->lockForUpdate()->firstOrFail();
                $campaign->increment('collected_amount', $donation->amount, $isNewDonor ? ['donor_count' => $campaign->donor_count + 1] : []);
                $donation->setRelation('campaign', $campaign);
                $receipt = $donation;
            }

            if ($status) {
                $donation->status = $status;
            }
            $donation->save();

            return $donation;
        });

        // Sent after commit so a mail failure cannot roll back a recorded payment.
        if ($receipt) {
            Notification::route('mail', $receipt->email)->notify(new DonationReceipt($receipt));
        }

        return $donation;
    }
}
