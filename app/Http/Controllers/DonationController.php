<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use App\Notifications\DonationReceipt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DonationController extends Controller
{
    public function create(Request $request, Campaign $campaign): View|RedirectResponse
    {
        abort_if($campaign->isDraft(), 404);

        if (! $campaign->isOpen()) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', __('This campaign is no longer accepting donations.'));
        }

        $campaign->load('campaigner');

        return view('donations.create', [
            'campaign' => $campaign,
            'presets' => config('infaqin.donation_presets'),
            'methods' => Donation::PAYMENT_METHODS,
            'prefillAmount' => $request->integer('amount') ?: null,
        ]);
    }

    public function store(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_if($campaign->isDraft(), 404);
        abort_unless($campaign->isOpen(), 422, __('This campaign is no longer accepting donations.'));

        $request->merge(['amount' => preg_replace('/\D/', '', (string) $request->input('amount'))]);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:'.Donation::MIN_AMOUNT, 'max:'.Donation::MAX_AMOUNT],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'payment_method' => ['required', Rule::in(array_keys(Donation::PAYMENT_METHODS))],
            'is_anonymous' => ['nullable', 'boolean'],
            'on_behalf_of' => ['nullable', 'string', 'max:100'],
        ], [
            'amount.min' => __('The minimum donation is :amount.', ['amount' => rupiah(Donation::MIN_AMOUNT)]),
            'amount.max' => __('The maximum donation is :amount.', ['amount' => rupiah(Donation::MAX_AMOUNT)]),
        ]);

        $donation = new Donation([
            'campaign_id' => $campaign->id,
            'amount' => (int) $validated['amount'],
            'donor_name' => $request->user()->name,
            'email' => $validated['email'],
            'payment_method' => $validated['payment_method'],
            'is_anonymous' => $request->boolean('is_anonymous'),
            'on_behalf_of' => isset($validated['on_behalf_of']) ? strip_tags($validated['on_behalf_of']) : null,
        ]);
        $donation->user()->associate($request->user());
        $donation->status = Donation::STATUS_PENDING;
        $donation->save();

        return redirect()->route('donations.show', $donation);
    }

    public function show(Request $request, Donation $donation): View
    {
        $this->authorizeOwner($request, $donation);
        $this->expireIfOverdue($donation);

        $donation->load(['campaign.campaigner', 'comment']);

        return view('donations.show', ['donation' => $donation]);
    }

    /**
     * Simulated payment gateway callback. Replace with a real gateway webhook in production.
     */
    public function confirm(Request $request, Donation $donation): RedirectResponse
    {
        $this->authorizeOwner($request, $donation);

        if ($this->expireIfOverdue($donation)) {
            return redirect()->route('donations.show', $donation)
                ->with('error', __('This payment expired after :hours hours. Please make a new donation.', ['hours' => Donation::PAYMENT_WINDOW_HOURS]));
        }

        $paid = DB::transaction(function () use ($donation) {
            $donation = Donation::whereKey($donation->id)->lockForUpdate()->firstOrFail();

            if ($donation->status !== Donation::STATUS_PENDING) {
                return null;
            }

            $isNewDonor = ! Donation::where('campaign_id', $donation->campaign_id)
                ->where('email', $donation->email)
                ->where('status', Donation::STATUS_PAID)
                ->exists();

            $donation->status = Donation::STATUS_PAID;
            $donation->paid_at = now();
            $donation->save();

            $campaign = Campaign::whereKey($donation->campaign_id)->lockForUpdate()->firstOrFail();
            $campaign->increment('collected_amount', $donation->amount, $isNewDonor ? ['donor_count' => $campaign->donor_count + 1] : []);

            return $donation->setRelation('campaign', $campaign);
        });

        if ($paid) {
            Notification::route('mail', $paid->email)->notify(new DonationReceipt($paid));
        }

        return redirect()->route('donations.show', $donation)
            ->with('status', __('Jazakallahu khairan! Your donation has been received.'));
    }

    public function cancel(Request $request, Donation $donation): RedirectResponse
    {
        $this->authorizeOwner($request, $donation);

        if ($donation->status === Donation::STATUS_PENDING) {
            $donation->status = Donation::STATUS_FAILED;
            $donation->save();
        }

        return redirect()->route('history')->with('status', __('Donation :reference was cancelled.', ['reference' => $donation->reference]));
    }

    /**
     * Pending donations past the payment window are cancelled (also done hourly by `donations:expire`).
     */
    private function expireIfOverdue(Donation $donation): bool
    {
        if (! $donation->isExpired()) {
            return false;
        }

        $donation->status = Donation::STATUS_FAILED;
        $donation->save();

        return true;
    }

    private function authorizeOwner(Request $request, Donation $donation): void
    {
        abort_unless($donation->user_id === $request->user()->id, 404);
    }
}
