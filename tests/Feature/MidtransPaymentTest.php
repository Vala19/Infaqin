<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Campaigner;
use App\Models\Category;
use App\Models\Donation;
use App\Models\User;
use App\Notifications\DonationReceipt;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithMidtrans;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use InteractsWithMidtrans;
    use RefreshDatabase;

    private const API = 'https://api.sandbox.midtrans.com/v2';

    private User $user;

    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureMidtrans();
        Notification::fake();

        $this->user = User::factory()->create(['name' => 'Aisyah']);
        $campaigner = Campaigner::create(['name' => 'Test Foundation', 'slug' => 'test-foundation', 'is_verified' => true]);
        $this->campaign = Campaign::create([
            'campaigner_id' => $campaigner->id,
            'title' => 'Clean water for the village',
            'slug' => 'clean-water',
            'category_id' => Category::where('slug', 'health')->value('id'),
            'summary' => 'Summary',
            'description' => 'Description',
            'target_amount' => 10_000_000,
            'deadline' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);
    }

    private function donation(array $attributes = []): Donation
    {
        $donation = new Donation(['campaign_id' => $this->campaign->id, 'amount' => 75_000, 'donor_name' => $this->user->name, 'email' => $this->user->email]);
        $donation->user()->associate($this->user);
        $donation->status = $attributes['status'] ?? Donation::STATUS_PENDING;
        $donation->snap_token = 'snap-token-123';
        if (isset($attributes['created_at'])) {
            $donation->created_at = $attributes['created_at'];
        }
        $donation->save();

        return $donation;
    }

    private function overdue(): Donation
    {
        return $this->donation(['created_at' => now()->subHours(Donation::PAYMENT_WINDOW_HOURS + 1)]);
    }

    // --- Creating the Snap transaction ---------------------------------------------------------

    public function test_store_requests_a_snap_token_and_opens_the_payment_page(): void
    {
        $this->fakeSnap('token-abc');

        $response = $this->actingAs($this->user)->post("/campaigns/{$this->campaign->slug}/donate", [
            'amount' => '75.000', 'email' => 'receipt@example.com',
        ]);

        $donation = $this->user->donations()->firstOrFail();
        $response->assertRedirect("/donations/{$donation->reference}")->assertSessionHas('open_snap', true);
        $this->assertSame(Donation::STATUS_PENDING, $donation->status);
        $this->assertSame('token-abc', $donation->snap_token);
        $this->assertNull($donation->payment_method);

        Http::assertSent(function (Request $request) use ($donation) {
            return $request->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('SB-Mid-server-test:'))
                && $request['transaction_details'] === ['order_id' => $donation->reference, 'gross_amount' => 75_000]
                && $request['item_details'] === [['id' => 'clean-water', 'price' => 75_000, 'quantity' => 1, 'name' => 'Clean water for the village']]
                && $request['customer_details'] === ['first_name' => 'Aisyah', 'email' => 'receipt@example.com']
                && $request['expiry']['unit'] === 'hour'
                && $request['expiry']['duration'] === Donation::PAYMENT_WINDOW_HOURS
                && $request['expiry']['start_time'] === $donation->created_at->format('Y-m-d H:i:s O');
        });

        $this->get("/donations/{$donation->reference}")->assertOk()
            ->assertSee('https://app.sandbox.midtrans.com/snap/snap.js', false)
            ->assertSee('data-client-key="SB-Mid-client-test"', false)
            ->assertSee('token-abc');
    }

    public function test_production_uses_the_production_snap_urls(): void
    {
        $this->configureMidtrans(production: true);
        Http::fake(['app.midtrans.com/snap/v1/transactions' => Http::response(['token' => 'live-token'], 201)]);

        $this->actingAs($this->user)->post("/campaigns/{$this->campaign->slug}/donate", ['amount' => '75.000', 'email' => $this->user->email]);

        $donation = $this->user->donations()->firstOrFail();
        Http::assertSent(fn (Request $request) => $request->url() === 'https://app.midtrans.com/snap/v1/transactions');
        $this->get("/donations/{$donation->reference}")
            ->assertSee('https://app.midtrans.com/snap/snap.js', false)
            ->assertDontSee('sandbox.midtrans.com', false);
    }

    public function test_no_donation_is_saved_when_snap_fails(): void
    {
        Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['error_messages' => ['boom']], 500)]);

        $this->actingAs($this->user)->from("/campaigns/{$this->campaign->slug}/donate")
            ->post("/campaigns/{$this->campaign->slug}/donate", ['amount' => '75.000', 'email' => $this->user->email])
            ->assertRedirect("/campaigns/{$this->campaign->slug}/donate")
            ->assertSessionHas('error');

        $this->assertSame(0, $this->user->donations()->count());
    }

    // --- Webhook ------------------------------------------------------------------------------

    public function test_notification_with_a_wrong_signature_is_rejected(): void
    {
        $donation = $this->donation();

        $this->notify($donation, ['signature_key' => str_repeat('0', 128)])->assertForbidden();
        $unsigned = $this->notification($donation);
        unset($unsigned['signature_key']);
        $this->postJson('/payments/midtrans/notification', $unsigned)->assertForbidden();

        // Signature made with a different server key.
        $forged = $this->notification($donation);
        $forged['signature_key'] = hash('sha512', $forged['order_id'].$forged['status_code'].$forged['gross_amount'].'wrong-key');
        $this->postJson('/payments/midtrans/notification', $forged)->assertForbidden();

        $this->assertSame(Donation::STATUS_PENDING, $donation->fresh()->status);
        $this->assertSame(0, $this->campaign->fresh()->collected_amount);
    }

    public function test_notification_with_a_different_amount_is_rejected(): void
    {
        $donation = $this->donation();

        $this->notify($donation, ['gross_amount' => '1000.00'])->assertStatus(422);
        $this->notify($donation, ['gross_amount' => '75000.50'])->assertStatus(422);

        $this->assertSame(Donation::STATUS_PENDING, $donation->fresh()->status);
    }

    public function test_notification_for_an_unknown_order_is_not_found(): void
    {
        $donation = $this->donation();

        $this->notify($donation, ['order_id' => 'INF-DOESNOTEXIST'])->assertNotFound();
    }

    public function test_webhook_does_not_need_a_csrf_token_and_is_throttled(): void
    {
        $route = app('router')->getRoutes()->getByName('payments.midtrans.notification');

        $this->assertSame('payments/midtrans/notification', $route->uri());
        $this->assertContains('throttle:120,1', $route->gatherMiddleware());
        $this->assertNotContains('auth', $route->gatherMiddleware());

        $csrf = new class(app(), app('encrypter')) extends ValidateCsrfToken
        {
            public function excludedPaths(): array
            {
                return $this->getExcludedPaths();
            }
        };
        $this->assertContains('payments/midtrans/notification', $csrf->excludedPaths());
    }

    public static function statusMapping(): array
    {
        return [
            'settlement' => ['settlement', null, Donation::STATUS_PAID],
            'capture accept' => ['capture', 'accept', Donation::STATUS_PAID],
            'capture challenge' => ['capture', 'challenge', Donation::STATUS_PENDING],
            'capture deny' => ['capture', 'deny', Donation::STATUS_FAILED],
            'expire' => ['expire', null, Donation::STATUS_EXPIRED],
            'deny' => ['deny', null, Donation::STATUS_FAILED],
            'cancel' => ['cancel', null, Donation::STATUS_FAILED],
            'failure' => ['failure', null, Donation::STATUS_FAILED],
            'pending' => ['pending', null, Donation::STATUS_PENDING],
        ];
    }

    #[DataProvider('statusMapping')]
    public function test_notification_status_mapping(string $transactionStatus, ?string $fraudStatus, string $expected): void
    {
        $donation = $this->donation();

        $this->notify($donation, array_filter([
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'status_code' => $expected === Donation::STATUS_PENDING ? '201' : '200',
        ]))->assertOk()->assertJson(['status' => $expected]);

        $donation->refresh();
        $this->assertSame($expected, $donation->status);
        $this->assertSame($expected === Donation::STATUS_PAID ? 75_000 : 0, $this->campaign->fresh()->collected_amount);
        $this->assertSame($expected === Donation::STATUS_PAID, $donation->paid_at !== null);
    }

    public function test_paid_notification_records_the_payment_and_sends_the_receipt(): void
    {
        $donation = $this->donation();

        $this->notify($donation, ['payment_type' => 'bank_transfer', 'va_numbers' => [['bank' => 'bca', 'va_number' => '12345678901']]])->assertOk();

        $donation->refresh();
        $this->assertSame(Donation::STATUS_PAID, $donation->status);
        $this->assertNotNull($donation->paid_at);
        $this->assertSame('bca_va', $donation->payment_method);
        $this->assertSame('BCA Virtual Account', $donation->paymentMethodLabel());

        $campaign = $this->campaign->fresh();
        $this->assertSame(75_000, $campaign->collected_amount);
        $this->assertSame(1, $campaign->donor_count);
        Notification::assertSentOnDemand(DonationReceipt::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === $this->user->email);
    }

    public function test_pending_notification_fills_in_the_payment_method(): void
    {
        $donation = $this->donation();

        $this->notify($donation, ['transaction_status' => 'pending', 'status_code' => '201', 'payment_type' => 'echannel'])->assertOk();

        $this->assertSame('mandiri_va', $donation->fresh()->payment_method);
        $this->assertSame(Donation::STATUS_PENDING, $donation->fresh()->status);
    }

    public function test_duplicate_notifications_count_the_donation_once(): void
    {
        $donation = $this->donation();

        $this->notify($donation)->assertOk();
        $this->notify($donation)->assertOk();
        $this->notify($donation, ['transaction_status' => 'capture', 'fraud_status' => 'accept'])->assertOk();

        $campaign = $this->campaign->fresh();
        $this->assertSame(75_000, $campaign->collected_amount);
        $this->assertSame(1, $campaign->donor_count);
        Notification::assertSentOnDemandTimes(DonationReceipt::class, 1);
    }

    public function test_a_repeat_donor_is_counted_once(): void
    {
        $this->notify($this->donation())->assertOk();
        $this->notify($this->donation())->assertOk();

        $campaign = $this->campaign->fresh();
        $this->assertSame(150_000, $campaign->collected_amount);
        $this->assertSame(1, $campaign->donor_count);
    }

    public function test_final_donations_never_change_again(): void
    {
        Http::fake([self::API.'/*/cancel' => Http::response(['status_code' => '200', 'transaction_status' => 'cancel'])]);
        $cancelled = $this->donation();
        $this->actingAs($this->user)->post("/donations/{$cancelled->reference}/cancel")->assertRedirect('/history');
        $expired = $this->donation(['status' => Donation::STATUS_EXPIRED]);
        $paid = $this->donation();
        $this->notify($paid)->assertOk();

        $this->notify($cancelled)->assertOk()->assertJson(['status' => Donation::STATUS_FAILED]);
        $this->notify($expired)->assertOk()->assertJson(['status' => Donation::STATUS_EXPIRED]);
        $this->notify($paid, ['transaction_status' => 'expire', 'status_code' => '407'])->assertOk()->assertJson(['status' => Donation::STATUS_PAID]);
        $this->notify($paid, ['transaction_status' => 'deny', 'status_code' => '202'])->assertOk()->assertJson(['status' => Donation::STATUS_PAID]);

        $this->assertSame(Donation::STATUS_FAILED, $cancelled->fresh()->status);
        $this->assertNull($cancelled->fresh()->paid_at);
        $this->assertSame(Donation::STATUS_EXPIRED, $expired->fresh()->status);
        $this->assertSame(Donation::STATUS_PAID, $paid->fresh()->status);
        $this->assertSame(75_000, $this->campaign->fresh()->collected_amount);
    }

    // --- Payment page -------------------------------------------------------------------------

    public function test_payment_page_and_status_poll_never_change_the_status(): void
    {
        $donation = $this->overdue();
        $this->actingAs($this->user);

        $this->get("/donations/{$donation->reference}")->assertOk()->assertSee('Pay now')->assertSee('Refresh status');
        $this->getJson("/donations/{$donation->reference}/status")->assertOk()->assertExactJson(['status' => 'pending']);

        $this->assertSame(Donation::STATUS_PENDING, $donation->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_status_poll_reports_the_webhook_result(): void
    {
        $donation = $this->donation();
        $this->notify($donation)->assertOk();

        $this->actingAs($this->user)->getJson("/donations/{$donation->reference}/status")->assertExactJson(['status' => 'paid']);
    }

    public function test_confirm_route_is_gone(): void
    {
        $donation = $this->donation();

        $this->actingAs($this->user)->post("/donations/{$donation->reference}/confirm")->assertNotFound();
        $this->assertSame(Donation::STATUS_PENDING, $donation->fresh()->status);
    }

    // --- Cancellation (BPMN 14) ---------------------------------------------------------------

    public function test_donor_can_cancel_a_pending_donation(): void
    {
        Http::fake([self::API.'/*/cancel' => Http::response(['status_code' => '200', 'transaction_status' => 'cancel'])]);
        $donation = $this->donation();

        $this->actingAs($this->user)->post("/donations/{$donation->reference}/cancel")->assertRedirect('/history');

        $this->assertSame(Donation::STATUS_FAILED, $donation->fresh()->status);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === self::API."/{$donation->reference}/cancel"
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('SB-Mid-server-test:')));
    }

    public function test_cancel_succeeds_when_midtrans_has_no_transaction(): void
    {
        $this->actingAs($this->user);

        Http::fake([self::API.'/*/cancel' => Http::response(['status_code' => '404', 'status_message' => "Transaction doesn't exist."])]);
        $bodyNotFound = $this->donation();
        $this->post("/donations/{$bodyNotFound->reference}/cancel")->assertRedirect('/history');
        $this->assertSame(Donation::STATUS_FAILED, $bodyNotFound->fresh()->status);
    }

    public function test_cancel_succeeds_when_midtrans_answers_http_404(): void
    {
        Http::fake([self::API.'/*/cancel' => Http::response([], 404)]);
        $donation = $this->donation();

        $this->actingAs($this->user)->post("/donations/{$donation->reference}/cancel")->assertRedirect('/history');
        $this->assertSame(Donation::STATUS_FAILED, $donation->fresh()->status);
    }

    public function test_cancel_that_midtrans_refuses_because_it_was_just_paid_records_the_payment(): void
    {
        $donation = $this->donation();
        Http::fake([
            self::API."/{$donation->reference}/cancel" => Http::response(['status_code' => '412', 'status_message' => 'Merchant cannot modify the status of the transaction']),
            self::API."/{$donation->reference}/status" => Http::response($this->notification($donation, ['payment_type' => 'gopay'])),
        ]);

        $this->actingAs($this->user)->post("/donations/{$donation->reference}/cancel")
            ->assertRedirect("/donations/{$donation->reference}")
            ->assertSessionHas('status', 'This donation could not be cancelled. Its current status is: Success.');

        $donation->refresh();
        $this->assertSame(Donation::STATUS_PAID, $donation->status);
        $this->assertSame('gopay', $donation->payment_method);
        $this->assertSame(75_000, $this->campaign->fresh()->collected_amount);
        Notification::assertSentOnDemandTimes(DonationReceipt::class, 1);
    }

    public function test_cancel_keeps_the_donation_pending_when_midtrans_errors(): void
    {
        $this->actingAs($this->user);

        Http::fake([self::API.'/*/cancel' => Http::response(['status_code' => '500'], 500)]);
        $serverError = $this->donation();
        $this->post("/donations/{$serverError->reference}/cancel")
            ->assertRedirect("/donations/{$serverError->reference}")
            ->assertSessionHas('error', 'We could not cancel this donation right now. Please try again.');
        $this->assertSame(Donation::STATUS_PENDING, $serverError->fresh()->status);
    }

    public function test_cancel_needs_a_200_status_code_in_the_body(): void
    {
        Http::fake([self::API.'/*/cancel' => Http::response(['status_message' => 'ok?'])]);
        $donation = $this->donation();

        $this->actingAs($this->user)->post("/donations/{$donation->reference}/cancel")->assertSessionHas('error');
        $this->assertSame(Donation::STATUS_PENDING, $donation->fresh()->status);
    }

    public function test_final_donations_cannot_be_cancelled(): void
    {
        Http::fake();
        $donation = $this->donation();
        $this->notify($donation)->assertOk();

        $this->actingAs($this->user)->post("/donations/{$donation->reference}/cancel")->assertSessionHas('error');

        $this->assertSame(Donation::STATUS_PAID, $donation->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_failed_label_covers_cancellations_and_rejected_payments(): void
    {
        $this->assertSame('Failed', Donation::STATUS_LABELS[Donation::STATUS_FAILED]);

        $donation = $this->donation(['status' => Donation::STATUS_FAILED]);

        $this->actingAs($this->user)->get('/history')->assertOk()->assertSee('Failed')->assertDontSee('Cancelled');
        $this->get("/donations/{$donation->reference}")->assertOk()->assertSee('Donation cancelled / payment failed');

        $this->withCookie('locale', 'id')->get('/history')->assertOk()->assertSee('Gagal')->assertDontSee('Dibatalkan');
        $this->withCookie('locale', 'id')->get("/donations/{$donation->reference}")
            ->assertOk()->assertSee('Donasi dibatalkan / pembayaran gagal');
    }

    // --- donations:expire (BPMN 12)-----------------------------------------------------------

    public function test_expire_command_checks_midtrans_before_changing_anything(): void
    {
        $missing = $this->overdue();
        $settled = $this->overdue();
        $stillPending = $this->overdue();
        $expiredAtMidtrans = $this->overdue();
        $unreachable = $this->overdue();
        $recent = $this->donation();

        Http::fake([
            self::API."/{$missing->reference}/status" => Http::response(['status_code' => '404', 'status_message' => "Transaction doesn't exist."]),
            self::API."/{$settled->reference}/status" => Http::response($this->notification($settled, ['payment_type' => 'qris'])),
            self::API."/{$stillPending->reference}/status" => Http::response($this->notification($stillPending, ['transaction_status' => 'pending', 'status_code' => '201'])),
            self::API."/{$expiredAtMidtrans->reference}/status" => Http::response($this->notification($expiredAtMidtrans, ['transaction_status' => 'expire', 'status_code' => '407'])),
            self::API."/{$unreachable->reference}/status" => Http::response('Bad gateway', 502),
        ]);

        $this->artisan('donations:expire')
            ->expectsOutputToContain('2 expired, 1 paid, 0 failed, 1 still pending, 1 skipped')
            ->assertSuccessful();

        $this->assertSame(Donation::STATUS_EXPIRED, $missing->fresh()->status);
        $this->assertSame(Donation::STATUS_PAID, $settled->fresh()->status);
        $this->assertSame(Donation::STATUS_PENDING, $stillPending->fresh()->status);
        $this->assertSame(Donation::STATUS_EXPIRED, $expiredAtMidtrans->fresh()->status);
        $this->assertSame(Donation::STATUS_PENDING, $unreachable->fresh()->status);
        $this->assertSame(Donation::STATUS_PENDING, $recent->fresh()->status);

        $this->assertSame(75_000, $this->campaign->fresh()->collected_amount);
        Notification::assertSentOnDemandTimes(DonationReceipt::class, 1);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), $recent->reference));
    }

    public function test_expire_command_treats_an_http_404_as_missing(): void
    {
        $donation = $this->overdue();
        Http::fake([self::API.'/*/status' => Http::response([], 404)]);

        $this->artisan('donations:expire')->assertSuccessful();

        $this->assertSame(Donation::STATUS_EXPIRED, $donation->fresh()->status);
    }

    public function test_expire_command_is_scheduled_hourly(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'donations:expire'));

        $this->assertNotNull($event);
        $this->assertSame('0 * * * *', $event->expression);
    }
}
