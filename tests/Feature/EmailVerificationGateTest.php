<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Campaigner;
use App\Models\Category;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\InteractsWithMidtrans;
use Tests\TestCase;

class EmailVerificationGateTest extends TestCase
{
    use InteractsWithMidtrans;
    use RefreshDatabase;

    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $campaigner = Campaigner::create(['name' => 'Test Foundation', 'slug' => 'test-foundation', 'is_verified' => true]);

        $this->campaign = Campaign::create([
            'campaigner_id' => $campaigner->id,
            'title' => 'Help the village',
            'slug' => 'help-the-village',
            'category_id' => Category::where('slug', 'health')->value('id'),
            'summary' => 'Summary',
            'description' => 'Description',
            'target_amount' => 10_000_000,
            'deadline' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);
    }

    private function donationPayload(User $user): array
    {
        return ['amount' => '50.000', 'email' => $user->email];
    }

    private function pendingDonation(User $user): Donation
    {
        $donation = new Donation(['campaign_id' => $this->campaign->id, 'amount' => 50_000, 'donor_name' => $user->name, 'email' => $user->email]);
        $donation->user()->associate($user);
        $donation->status = Donation::STATUS_PENDING;
        $donation->save();

        return $donation;
    }

    public function test_unverified_user_cannot_open_or_submit_the_donation_form(): void
    {
        $user = User::factory()->unverified()->create();
        $slug = $this->campaign->slug;

        $this->actingAs($user)->get("/campaigns/{$slug}/donate")->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->post("/campaigns/{$slug}/donate", $this->donationPayload($user))->assertRedirect(route('verification.notice'));

        $this->assertSame(0, $user->donations()->count());
    }

    public function test_unverified_user_cannot_open_or_pay_a_donation(): void
    {
        $user = User::factory()->unverified()->create();
        $donation = $this->pendingDonation($user);

        $this->actingAs($user)->get("/donations/{$donation->reference}")->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->getJson("/donations/{$donation->reference}/status")->assertForbidden();

        $this->assertSame(Donation::STATUS_PENDING, $donation->fresh()->status);
    }

    public function test_verified_user_can_open_and_submit_the_donation_form(): void
    {
        $this->configureMidtrans();
        $this->fakeSnap();
        $user = User::factory()->create();
        $slug = $this->campaign->slug;

        $this->actingAs($user)->get("/campaigns/{$slug}/donate")->assertOk();
        $this->actingAs($user)->post("/campaigns/{$slug}/donate", $this->donationPayload($user))->assertSessionHasNoErrors();

        $donation = $user->donations()->firstOrFail();
        $this->assertSame(50_000, $donation->amount);

        $this->actingAs($user)->get("/donations/{$donation->reference}")->assertOk();
    }

    public function test_notice_explains_why_and_offers_to_resend_the_link(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/email/verify')->assertOk()
            ->assertSee('Email verification is required before you can donate.')
            ->assertSee($user->email)
            ->assertSee(route('verification.send'), false);

        $this->actingAs($user)->withCookie('locale', 'id')->get('/email/verify')->assertOk()
            ->assertSee('Verifikasi email diperlukan sebelum Anda dapat berdonasi.')
            ->assertSee('Kirim ulang tautan verifikasi');

        $this->actingAs($user)->from('/email/verify')->post('/email/verification-notification')
            ->assertRedirect('/email/verify')
            ->assertSessionHas('status');
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_user_skips_the_notice(): void
    {
        $this->actingAs(User::factory()->create())->get('/email/verify')->assertRedirect('/profile');
    }

    public function test_verifying_returns_the_donor_to_the_donation_form(): void
    {
        $user = User::factory()->unverified()->create();
        $donateUrl = route('donations.create', $this->campaign);

        $this->actingAs($user)->get($donateUrl)->assertRedirect(route('verification.notice'));

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);

        $this->get($url)->assertRedirect($donateUrl);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_donate_button_is_shown_to_everyone(): void
    {
        $page = "/campaigns/{$this->campaign->slug}";
        $donateUrl = route('donations.create', $this->campaign);

        $this->get($page)->assertOk()->assertSee($donateUrl, false);
        $this->actingAs(User::factory()->unverified()->create())->get($page)->assertOk()->assertSee($donateUrl, false);
        $this->actingAs(User::factory()->create())->get($page)->assertOk()->assertSee($donateUrl, false);
    }
}
