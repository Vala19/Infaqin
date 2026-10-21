<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Services\DonationStatusUpdater;
use App\Services\MidtransClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Midtrans HTTP notification (the main payment path, CLAUDE.md rule 3). Excluded from CSRF in bootstrap/app.php.
 */
class MidtransNotificationController extends Controller
{
    public function __invoke(Request $request, MidtransClient $midtrans, DonationStatusUpdater $updater): JsonResponse
    {
        $payload = $request->all();

        if (! $midtrans->verifySignature($payload)) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $donation = Donation::where('reference', (string) ($payload['order_id'] ?? ''))->first();

        if (! $donation) {
            return response()->json(['message' => 'Unknown order.'], 404);
        }

        // Midtrans sends "50000.00"; only whole rupiah that match the donation are accepted.
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        if (! preg_match('/^\d+(\.0+)?$/', $grossAmount) || (int) $grossAmount !== $donation->amount) {
            return response()->json(['message' => 'Amount mismatch.'], 422);
        }

        $donation = $updater->apply($donation, $payload);

        return response()->json(['status' => $donation->status]);
    }
}
