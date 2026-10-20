<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Campaigner;
use App\Models\Category;
use App\Models\Donation;
use App\Models\User;
use App\Notifications\DonationReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DonationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(array $attributes = []): Campaign
    {
        $campaigner = Campaigner::create(['name' => 'Test Foundation', 'slug' => 'test-foundation', 'is_verified' => true]);

        return Campaign::create($attributes + [
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

    public function test_full_donation_flow_updates_campaign_and_unlocks_prayer(): void
    {
        $user = User::factory()->create();
        $campaign = $this->campaign();

        $this->actingAs($user)->post("/campaigns/{$campaign->slug}/donate", [
            'amount' => '150.000',
            'email' => $user->email,
            'payment_method' => 'qris',
            'is_anonymous' => '1',
            'on_behalf_of' => 'My mother',
        ])->assertRedirect();

        $donation = $user->donations()->firstOrFail();
        $this->assertSame(150_000, $donation->amount);
        $this->assertSame('pending', $donation->status);
        $this->assertSame('Hamba Allah', $donation->publicName());

        // Prayer is locked until the donation is paid.
        $this->post("/campaigns/{$campaign->slug}/comments", ['body' => 'Semoga berkah'])->assertSessionHas('error');

        $this->post("/donations/{$donation->reference}/confirm")->assertRedirect();
        $this->post("/donations/{$donation->reference}/confirm"); // idempotent

        $campaign->refresh();
        $this->assertSame(150_000, $campaign->collected_amount);
        $this->assertSame(1, $campaign->donor_count);

        $this->post("/campaigns/{$campaign->slug}/comments", ['body' => 'Semoga berkah'])->assertSessionHasNoErrors();
        $comment = $user->comments()->firstOrFail();

        $this->postJson("/comments/{$comment->id}/amen")->assertJson(['amened' => true, 'count' => 1]);
        $this->postJson("/comments/{$comment->id}/amen")->assertJson(['amened' => false, 'count' => 0]);
    }

    public function test_confirming_sends_receipt_and_expired_payments_cannot_be_confirmed(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $campaign = $this->campaign();

        $make = function () use ($user, $campaign) {
            $donation = new Donation(['campaign_id' => $campaign->id, 'amount' => 50_000, 'donor_name' => $user->name, 'email' => $user->email, 'payment_method' => 'qris']);
            $donation->user()->associate($user);
            $donation->status = 'pending';
            $donation->save();

            return $donation;
        };

        $this->actingAs($user);

        $fresh = $make();
        $this->post("/donations/{$fresh->reference}/confirm");
        Notification::assertSentOnDemand(DonationReceipt::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === $user->email);

        $stale = $make();
        $stale->forceFill(['created_at' => now()->subHours(Donation::PAYMENT_WINDOW_HOURS + 1)])->save();
        $this->post("/donations/{$stale->reference}/confirm")->assertSessionHas('error');
        $this->assertSame('failed', $stale->fresh()->status);
        $this->assertSame(50_000, $campaign->fresh()->collected_amount);

        $old = $make();
        $old->forceFill(['created_at' => now()->subDays(2)])->save();
        $this->artisan('donations:expire')->assertSuccessful();
        $this->assertSame('failed', $old->fresh()->status);
    }

    public function test_donation_validation(): void
    {
        $user = User::factory()->create();
        $campaign = $this->campaign();

        $this->actingAs($user)->post("/campaigns/{$campaign->slug}/donate", [
            'amount' => '5000', 'email' => 'not-an-email', 'payment_method' => 'bitcoin',
        ])->assertSessionHasErrors(['amount', 'email', 'payment_method']);
    }

    public function test_closed_campaign_rejects_donations(): void
    {
        $campaign = $this->campaign(['status' => 'closed']);

        $this->actingAs(User::factory()->create())
            ->get("/campaigns/{$campaign->slug}/donate")->assertRedirect("/campaigns/{$campaign->slug}");
    }

    public function test_users_cannot_see_or_confirm_other_users_donations(): void
    {
        $owner = User::factory()->create();
        $campaign = $this->campaign();

        $donation = new Donation(['campaign_id' => $campaign->id, 'amount' => 50_000, 'donor_name' => 'Owner', 'email' => $owner->email, 'payment_method' => 'qris']);
        $donation->user()->associate($owner);
        $donation->status = 'pending';
        $donation->save();

        $this->actingAs(User::factory()->create());
        $this->get("/donations/{$donation->reference}")->assertNotFound();
        $this->post("/donations/{$donation->reference}/confirm")->assertNotFound();
        $this->assertSame('pending', $donation->fresh()->status);
    }
}
