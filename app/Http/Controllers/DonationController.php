<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use App\Services\DonationStatusUpdater;
use App\Services\MidtransClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

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
            'prefillAmount' => $request->integer('amount') ?: null,
        ]);
    }

    public function store(Request $request, Campaign $campaign, MidtransClient $midtrans): RedirectResponse
    {
        abort_if($campaign->isDraft(), 404);
        abort_unless($campaign->isOpen(), 422, __('This campaign is no longer accepting donations.'));

        $request->merge(['amount' => preg_replace('/\D/', '', (string) $request->input('amount'))]);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:'.Donation::MIN_AMOUNT, 'max:'.Donation::MAX_AMOUNT],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'is_anonymous' => ['nullable', 'boolean'],
            'on_behalf_of' => ['nullable', 'string', 'max:100'],
        ], [
            'amount.min' => __('The minimum donation is :amount.', ['amount' => rupiah(Donation::MIN_AMOUNT)]),
            'amount.max' => __('The maximum donation is :amount.', ['amount' => rupiah(Donation::MAX_AMOUNT)]),
        ]);

        // The payment method is chosen in the Snap popup and filled in from the Midtrans notification.
        $donation = new Donation([
            'campaign_id' => $campaign->id,
            'amount' => (int) $validated['amount'],
            'donor_name' => $request->user()->name,
            'email' => $validated['email'],
            'is_anonymous' => $request->boolean('is_anonymous'),
            'on_behalf_of' => isset($validated['on_behalf_of']) ? strip_tags($validated['on_behalf_of']) : null,
        ]);
        $donation->reference = Donation::generateReference();
        $donation->setCreatedAt(now());
        $donation->setRelation('campaign', $campaign);
        $donation->user()->associate($request->user());
        $donation->status = Donation::STATUS_PENDING;

        // Ask Midtrans first so a gateway failure leaves no unpayable pending donation behind.
        try {
            $donation->snap_token = $midtrans->createSnapToken($donation);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', __('We could not reach the payment gateway. Please try again in a moment.'));
        }

        $donation->save();

        return redirect()->route('donations.show', $donation)->with('open_snap', true);
    }

    /**
     * Read-only: the countdown is informational and never changes the status (rule 3).
     */
    public function show(Request $request, Donation $donation, MidtransClient $midtrans): View
    {
        $this->authorizeOwner($request, $donation);

        $donation->load(['campaign.campaigner', 'comment']);

        return view('donations.show', [
            'donation' => $donation,
            'snapJsUrl' => $midtrans->snapJsUrl(),
            'clientKey' => $midtrans->clientKey(),
        ]);
    }

    /**
     * Read-only status for the payment page to poll after the Snap popup closes.
     */
    public function status(Request $request, Donation $donation): JsonResponse
    {
        $this->authorizeOwner($request, $donation);

        return response()->json(['status' => $donation->status]);
    }

    public function cancel(Request $request, Donation $donation, MidtransClient $midtrans, DonationStatusUpdater $updater): RedirectResponse
    {
        $this->authorizeOwner($request, $donation);

        if ($donation->status !== Donation::STATUS_PENDING) {
            return redirect()->route('donations.show', $donation)->with('error', __('This donation can no longer be cancelled.'));
        }

        try {
            if ($midtrans->cancel($donation->reference) === MidtransClient::NOT_CANCELLABLE) {
                // Midtrans refused (412), usually because the donor just paid: sync and show what really happened.
                $transaction = $midtrans->status($donation->reference);
                $donation = $transaction ? $updater->apply($donation, $transaction) : $donation;

                return redirect()->route('donations.show', $donation)->with(
                    $donation->status === Donation::STATUS_PAID ? 'status' : 'error',
                    __('This donation could not be cancelled. Its current status is: :status.', ['status' => __(Donation::STATUS_LABELS[$donation->status])])
                );
            }
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('donations.show', $donation)->with('error', __('We could not cancel this donation right now. Please try again.'));
        }

        $updater->cancelByDonor($donation);

        return redirect()->route('history')->with('status', __('Donation :reference was cancelled.', ['reference' => $donation->reference]));
    }

    private function authorizeOwner(Request $request, Donation $donation): void
    {
        abort_unless($donation->user_id === $request->user()->id, 404);
    }
}
