<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Campaigner;
use App\Models\Category;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMidtrans;
use Tests\TestCase;

class DonationFlowTest extends TestCase
{
    use InteractsWithMidtrans;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureMidtrans();
    }

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
        $this->fakeSnap();
        $user = User::factory()->create();
        $campaign = $this->campaign();

        $this->actingAs($user)->post("/campaigns/{$campaign->slug}/donate", [
            'amount' => '150.000',
            'email' => $user->email,
            'is_anonymous' => '1',
            'on_behalf_of' => 'My mother',
        ])->assertRedirect();

        $donation = $user->donations()->firstOrFail();
        $this->assertSame(150_000, $donation->amount);
        $this->assertSame('pending', $donation->status);
        $this->assertSame('Hamba Allah', $donation->publicName());

        // Prayer is locked until the donation is paid.
        $this->post("/campaigns/{$campaign->slug}/comments", ['body' => 'Semoga berkah'])->assertSessionHas('error');

        $this->notify($donation)->assertOk();

        $campaign->refresh();
        $this->assertSame(150_000, $campaign->collected_amount);
        $this->assertSame(1, $campaign->donor_count);

        $this->post("/campaigns/{$campaign->slug}/comments", ['body' => 'Semoga berkah'])->assertSessionHasNoErrors();
        $comment = $user->comments()->firstOrFail();

        $this->postJson("/comments/{$comment->id}/amen")->assertJson(['amened' => true, 'count' => 1]);
        $this->postJson("/comments/{$comment->id}/amen")->assertJson(['amened' => false, 'count' => 0]);
    }

    public function test_donation_validation(): void
    {
        $user = User::factory()->create();
        $campaign = $this->campaign();

        $this->actingAs($user)->post("/campaigns/{$campaign->slug}/donate", [
            'amount' => '5000', 'email' => 'not-an-email',
        ])->assertSessionHasErrors(['amount', 'email']);
    }

    public function test_donation_form_has_no_payment_method_choice(): void
    {
        $campaign = $this->campaign();

        $this->actingAs(User::factory()->create())->get("/campaigns/{$campaign->slug}/donate")
            ->assertOk()
            ->assertDontSee('name="payment_method"', false)
            ->assertSee('Chosen in the payment window');
    }

    public function test_closed_campaign_rejects_donations(): void
    {
        $campaign = $this->campaign(['status' => 'closed']);

        $this->actingAs(User::factory()->create())
            ->get("/campaigns/{$campaign->slug}/donate")->assertRedirect("/campaigns/{$campaign->slug}");
    }

    public function test_users_cannot_see_poll_or_cancel_other_users_donations(): void
    {
        $owner = User::factory()->create();
        $campaign = $this->campaign();

        $donation = new Donation(['campaign_id' => $campaign->id, 'amount' => 50_000, 'donor_name' => 'Owner', 'email' => $owner->email]);
        $donation->user()->associate($owner);
        $donation->status = 'pending';
        $donation->save();

        $this->actingAs(User::factory()->create());
        $this->get("/donations/{$donation->reference}")->assertNotFound();
        $this->getJson("/donations/{$donation->reference}/status")->assertNotFound();
        $this->post("/donations/{$donation->reference}/cancel")->assertNotFound();
        $this->assertSame('pending', $donation->fresh()->status);
    }
}
