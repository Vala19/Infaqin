<?php

namespace Tests\Concerns;

use App\Models\Donation;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

trait InteractsWithMidtrans
{
    protected function configureMidtrans(bool $production = false): void
    {
        config([
            'services.midtrans.server_key' => 'SB-Mid-server-test',
            'services.midtrans.client_key' => 'SB-Mid-client-test',
            'services.midtrans.is_production' => $production,
        ]);
    }

    protected function fakeSnap(string $token = 'snap-token-123'): void
    {
        Http::fake(['app.sandbox.midtrans.com/snap/v1/transactions' => Http::response(['token' => $token, 'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/'.$token], 201)]);
    }

    /**
     * A Midtrans HTTP notification for the donation, signed with the test server key unless overridden.
     */
    protected function notification(Donation $donation, array $overrides = []): array
    {
        $payload = $overrides + [
            'order_id' => $donation->reference,
            'status_code' => '200',
            'gross_amount' => $donation->amount.'.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-'.$donation->reference,
        ];

        $payload['signature_key'] ??= hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('services.midtrans.server_key'));

        return $payload;
    }

    protected function notify(Donation $donation, array $overrides = []): TestResponse
    {
        return $this->postJson('/payments/midtrans/notification', $this->notification($donation, $overrides));
    }
}
