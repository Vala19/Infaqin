<?php

namespace App\Services;

use App\Models\Donation;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Thin wrapper around the Midtrans Snap and Core APIs, built on Laravel's Http client so tests can use Http::fake().
 */
class MidtransClient
{
    /** Cancel result: Midtrans no longer accepts payment for the transaction (or never knew it). */
    public const CANCELLED = 'cancelled';

    /** Cancel result: Midtrans refused because the status can no longer change (HTTP/body 412), e.g. it was just paid. */
    public const NOT_CANCELLABLE = 'not_cancellable';

    public function isProduction(): bool
    {
        return (bool) config('services.midtrans.is_production');
    }

    public function clientKey(): ?string
    {
        return config('services.midtrans.client_key');
    }

    public function snapJsUrl(): string
    {
        return $this->snapBaseUrl().'/snap/snap.js';
    }

    /**
     * Creates a Snap transaction for the donation and returns its token. `reference` is the Midtrans order_id.
     *
     * @throws RequestException|RuntimeException
     */
    public function createSnapToken(Donation $donation): string
    {
        $response = $this->request()->post($this->snapBaseUrl().'/snap/v1/transactions', [
            'transaction_details' => [
                'order_id' => $donation->reference,
                'gross_amount' => (int) $donation->amount,
            ],
            'item_details' => [[
                'id' => $donation->campaign->slug,
                'price' => (int) $donation->amount,
                'quantity' => 1,
                'name' => Str::limit($donation->campaign->title, 47),
            ]],
            'customer_details' => [
                'first_name' => $donation->donor_name,
                'email' => $donation->email,
            ],
            'expiry' => [
                'start_time' => $donation->created_at->format('Y-m-d H:i:s O'),
                'unit' => 'hour',
                'duration' => Donation::PAYMENT_WINDOW_HOURS,
            ],
        ])->throw();

        return $response->json('token') ?? throw new RuntimeException('Midtrans did not return a Snap token.');
    }

    /**
     * Returns the transaction status payload, or null when Midtrans does not know the order (404).
     *
     * @throws RequestException|RuntimeException
     */
    public function status(string $orderId): ?array
    {
        $response = $this->request()->get($this->apiBaseUrl().'/v2/'.rawurlencode($orderId).'/status');

        if ($this->statusCode($response) === '404') {
            return null;
        }

        // Expired (407) and denied (202) transactions still carry transaction_status, so key on that, not on the code.
        if ($response->successful() && is_string($response->json('transaction_status'))) {
            return $response->json();
        }

        $this->fail($response);
    }

    /**
     * Cancels the transaction so it can no longer be paid. Returns self::CANCELLED or self::NOT_CANCELLABLE.
     *
     * @throws RequestException|RuntimeException
     */
    public function cancel(string $orderId): string
    {
        $response = $this->request()->post($this->apiBaseUrl().'/v2/'.rawurlencode($orderId).'/cancel');
        $bodyCode = (string) $response->json('status_code');

        return match (true) {
            // Success only when Midtrans says so in the body, not merely HTTP 200.
            $bodyCode === '200' => self::CANCELLED,
            // 404: the donor closed Snap before choosing a method, so there is nothing to cancel.
            $bodyCode === '404' || $response->status() === 404 => self::CANCELLED,
            $bodyCode === '412' || $response->status() === 412 => self::NOT_CANCELLABLE,
            default => $this->fail($response),
        };
    }

    /**
     * signature_key = sha512(order_id + status_code + gross_amount + server_key).
     */
    public function verifySignature(array $payload): bool
    {
        $serverKey = (string) config('services.midtrans.server_key');
        $signature = $payload['signature_key'] ?? null;

        if ($serverKey === '' || ! is_string($signature)) {
            return false;
        }

        $expected = hash('sha512', ($payload['order_id'] ?? '').($payload['status_code'] ?? '').($payload['gross_amount'] ?? '').$serverKey);

        return hash_equals($expected, $signature);
    }

    /**
     * Midtrans reports errors both as HTTP status and as `status_code` in a 200 body, so prefer the body.
     */
    private function statusCode(Response $response): string
    {
        return (string) ($response->json('status_code') ?? $response->status());
    }

    private function fail(Response $response): never
    {
        $response->throw();

        throw new RuntimeException('Midtrans responded with status_code '.$this->statusCode($response).'.');
    }

    private function request(): PendingRequest
    {
        return Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }

    private function snapBaseUrl(): string
    {
        return $this->isProduction() ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
    }

    private function apiBaseUrl(): string
    {
        return $this->isProduction() ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }
}
