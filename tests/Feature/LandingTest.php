<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Comment;
use App\Models\Donation;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function prayer(Campaign $campaign, User $user, string $body, bool $anonymous = false): Comment
    {
        $donation = new Donation([
            'campaign_id' => $campaign->id,
            'amount' => 50_000,
            'donor_name' => $user->name,
            'email' => $user->email,
            'is_anonymous' => $anonymous,
        ]);
        $donation->user()->associate($user);
        $donation->status = Donation::STATUS_PAID;
        $donation->paid_at = now();
        $donation->save();

        $comment = new Comment(['body' => $body]);
        $comment->campaign()->associate($campaign);
        $comment->user()->associate($user);
        $comment->donation()->associate($donation);
        $comment->save();

        return $comment;
    }

    public function test_ledger_card_shows_the_latest_withdrawal_of_a_public_campaign(): void
    {
        $active = Campaign::active()->firstOrFail();
        $draft = Campaign::where('status', Campaign::STATUS_DRAFT)->firstOrFail();

        $active->withdrawals()->create(['amount' => 4_250_000, 'description' => 'Forty sacks of rice for the shelter', 'withdrawn_at' => now()->subHour()]);
        // Newer, but on a draft: must never reach the public page.
        $draft->withdrawals()->create(['amount' => 9_000_000, 'description' => 'Draft-only purchase', 'withdrawn_at' => now()]);

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Latest withdrawal', $active->title, 'Rp4.250.000', 'Forty sacks of rice for the shelter'])
            ->assertDontSee('Draft-only purchase');
    }

    public function test_ledger_card_is_plain_when_nothing_was_withdrawn(): void
    {
        Withdrawal::query()->delete();

        $this->get('/')->assertOk()
            ->assertSee('Open Fund Ledger')
            ->assertDontSee('Latest withdrawal');
    }

    public function test_prayer_card_shows_the_latest_prayer_and_hides_anonymous_names(): void
    {
        $active = Campaign::active()->firstOrFail();
        $draft = Campaign::where('status', Campaign::STATUS_DRAFT)->firstOrFail();
        $donor = User::factory()->create(['name' => 'Siti Rahmawati Quietgiver']);

        $this->prayer($active, $donor, 'Semoga sumurnya cepat mengalir', anonymous: true);
        $this->travel(1)->minutes();
        $this->prayer($draft, User::factory()->create(), 'A prayer on a draft campaign');

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Latest prayer', 'Semoga sumurnya cepat mengalir', 'Hamba Allah, 0 people said Aamiin'])
            ->assertDontSee('Siti Rahmawati Quietgiver')
            ->assertDontSee('A prayer on a draft campaign');
    }

    public function test_hero_chip_names_the_campaigner_of_each_slide(): void
    {
        $slide = Campaign::active()->with('campaigner')
            ->orderByDesc('is_featured')->orderByDesc('is_urgent')->latest()
            ->firstOrFail();

        $this->get('/')->assertOk()
            ->assertSee($slide->campaigner->name)
            ->assertSee(__(':count donors', ['count' => number_format($slide->donor_count)]))
            ->assertDontSee('Raised together');
    }

    public function test_landing_renders_in_indonesian(): void
    {
        $this->withCookie('locale', 'id')->get('/')->assertOk()
            ->assertSee('Pencairan terbaru')
            ->assertSee('Doa terbaru')
            ->assertSee('Coba kalkulatornya')
            ->assertDontSee('Latest withdrawal');
    }
}
