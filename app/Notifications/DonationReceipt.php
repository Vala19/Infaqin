<?php

namespace App\Notifications;

use App\Models\Donation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DonationReceipt extends Notification
{
    public function __construct(private Donation $donation)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $donation = $this->donation;

        $mail = (new MailMessage)
            ->subject(__('Your Infaqin receipt :reference', ['reference' => $donation->reference]))
            ->greeting('Jazakallahu khairan!')
            ->line(__('We have received your donation to ":campaign".', ['campaign' => $donation->campaign->title]))
            ->line(__('Reference').': '.$donation->reference)
            ->line(__('Amount').': '.rupiah($donation->amount))
            ->line(__('Payment method').': '.$donation->paymentMethodLabel())
            ->line(__('Shown publicly as').': '.$donation->publicName());

        if ($donation->on_behalf_of) {
            $mail->line(__('On behalf of').': '.$donation->on_behalf_of);
        }

        return $mail
            ->action(__('View receipt'), route('donations.show', $donation))
            ->line(__('You can follow how the funds are used on the campaign\'s Open Fund Ledger.'));
    }
}
