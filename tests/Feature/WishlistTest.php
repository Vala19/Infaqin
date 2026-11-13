<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Campaigner;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function campaign(string $title, string $status = Campaign::STATUS_ACTIVE): Campaign
    {
        return Campaign::create([
            'campaigner_id' => Campaigner::first()->id,
            'category_id' => Category::where('slug', 'health')->value('id'),
            'title' => $title,
            'slug' => str($title)->slug(),
            'summary' => 'Summary',
            'description' => 'Description',
            'target_amount' => 10_000_000,
            'deadline' => now()->addMonth()->toDateString(),
            'status' => $status,
        ]);
    }

    private function wishlistRows(User $user, Campaign $campaign): int
    {
        return DB::table('wishlists')->where('user_id', $user->id)->where('campaign_id', $campaign->id)->count();
    }

    public function test_saving_and_removing_is_idempotent(): void
    {
        $user = User::factory()->create();
        $campaign = $this->campaign('Clean Water Wells');

        $this->actingAs($user)->postJson("/campaigns/{$campaign->slug}/wishlist", ['saved' => true])
            ->assertOk()->assertExactJson(['saved' => true]);
        $this->actingAs($user)->postJson("/campaigns/{$campaign->slug}/wishlist", ['saved' => true])
            ->assertOk()->assertExactJson(['saved' => true]);

        $this->assertSame(1, $this->wishlistRows($user, $campaign));
        $this->assertNotNull(DB::table('wishlists')->where('user_id', $user->id)->value('created_at'));

        $this->actingAs($user)->postJson("/campaigns/{$campaign->slug}/wishlist", ['saved' => false])
            ->assertOk()->assertExactJson(['saved' => false]);
        $this->actingAs($user)->postJson("/campaigns/{$campaign->slug}/wishlist", ['saved' => false])
            ->assertOk()->assertExactJson(['saved' => false]);

        $this->assertSame(0, $this->wishlistRows($user, $campaign));
    }

    public function test_unverified_accounts_can_use_the_wishlist(): void
    {
        $user = User::factory()->unverified()->create();
        $campaign = $this->campaign('Clean Water Wells');

        $this->actingAs($user)->postJson("/campaigns/{$campaign->slug}/wishlist", ['saved' => true])->assertOk();
        $this->assertSame(1, $this->wishlistRows($user, $campaign));

        $this->actingAs($user)->get('/wishlist')->assertOk()->assertSee('Clean Water Wells');
    }

    public function test_form_submission_without_javascript_redirects_back(): void
    {
        $user = User::factory()->create();
        $campaign = $this->campaign('Clean Water Wells');

        $this->actingAs($user)->from("/campaigns/{$campaign->slug}")
            ->post("/campaigns/{$campaign->slug}/wishlist", ['saved' => '1'])
            ->assertRedirect("/campaigns/{$campaign->slug}")
            ->assertSessionHas('status', 'Saved to your wishlist.');

        $this->assertSame(1, $this->wishlistRows($user, $campaign));
    }

    public function test_saved_state_is_required(): void
    {
        $user = User::factory()->create();
        $campaign = $this->campaign('Clean Water Wells');

        $this->actingAs($user)->postJson("/campaigns/{$campaign->slug}/wishlist")->assertUnprocessable()->assertJsonValidationErrors('saved');
        $this->actingAs($user)->postJson("/campaigns/{$campaign->slug}/wishlist", ['saved' => 'maybe'])->assertJsonValidationErrors('saved');
    }

    public function test_saving_is_throttled(): void
    {
        $user = User::factory()->create();
        $campaign = $this->campaign('Clean Water Wells');

        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($user)->postJson("/campaigns/{$campaign->slug}/wishlist", ['saved' => $i % 2 === 0])->assertOk();
        }

        $this->actingAs($user)->postJson("/campaigns/{$campaign->slug}/wishlist", ['saved' => true])->assertTooManyRequests();
    }

    public function test_guests_cannot_save_and_are_sent_to_login(): void
    {
        $campaign = $this->campaign('Clean Water Wells');

        $this->post("/campaigns/{$campaign->slug}/wishlist", ['saved' => '1'])->assertRedirect('/login');
        $this->get('/wishlist')->assertRedirect('/login');

        $this->assertSame(0, DB::table('wishlists')->where('campaign_id', $campaign->id)->count());
    }

    private function donor(): User
    {
        return User::where('email', 'user@infaqin.test')->firstOrFail();
    }

    public function test_guest_heart_saves_the_campaign_after_login(): void
    {
        $campaign = $this->campaign('Clean Water Wells');
        $heart = route('wishlist.campaign', $campaign);

        $this->get("/campaigns/{$campaign->slug}")->assertOk()->assertSee('href="'.$heart.'"', false);

        // The GET only remembers the intent; nothing is written before login.
        $this->get($heart)->assertRedirect('/login')->assertSessionHas('wishlist.intent', $campaign->id);
        $this->assertSame(0, $this->wishlistRows($this->donor(), $campaign));

        $this->post('/login', ['email' => 'user@infaqin.test', 'password' => 'Donor#12345'])
            ->assertRedirect(route('campaigns.show', $campaign))
            ->assertSessionHas('status', 'Saved to wishlist')
            ->assertSessionMissing('wishlist.intent');

        $this->assertSame(1, $this->wishlistRows($this->donor(), $campaign));

        $this->get(route('campaigns.show', $campaign))->assertOk()
            ->assertSee('Saved to wishlist')
            ->assertSee('aria-pressed="true"', false);

        // Logged in, the heart link just leads back to the campaign.
        $this->get($heart)->assertRedirect(route('campaigns.show', $campaign));
    }

    public function test_guest_heart_on_an_already_saved_campaign_keeps_one_row(): void
    {
        $campaign = $this->campaign('Clean Water Wells');
        $this->donor()->wishlist()->attach($campaign);

        $this->get(route('wishlist.campaign', $campaign));
        $this->post('/login', ['email' => 'user@infaqin.test', 'password' => 'Donor#12345'])
            ->assertRedirect(route('campaigns.show', $campaign));

        $this->assertSame(1, $this->wishlistRows($this->donor(), $campaign));
    }

    public function test_wishlist_intent_survives_a_failed_login(): void
    {
        $campaign = $this->campaign('Clean Water Wells');

        $this->get(route('wishlist.campaign', $campaign));
        $this->post('/login', ['email' => 'user@infaqin.test', 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        $this->assertSame(0, $this->wishlistRows($this->donor(), $campaign));

        $this->post('/login', ['email' => 'user@infaqin.test', 'password' => 'Donor#12345'])
            ->assertRedirect(route('campaigns.show', $campaign));
        $this->assertSame(1, $this->wishlistRows($this->donor(), $campaign));
    }

    public function test_wishlist_intent_is_ignored_when_the_campaign_became_a_draft(): void
    {
        $campaign = $this->campaign('Clean Water Wells');

        $this->get(route('wishlist.campaign', $campaign))->assertRedirect('/login');
        $campaign->update(['status' => Campaign::STATUS_DRAFT]);

        $this->post('/login', ['email' => 'user@infaqin.test', 'password' => 'Donor#12345'])
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'Welcome back, '.$this->donor()->name.'!')
            ->assertSessionMissing('wishlist.intent');

        $this->assertSame(0, $this->wishlistRows($this->donor(), $campaign));
    }

    public function test_saved_to_wishlist_message_is_translated(): void
    {
        $campaign = $this->campaign('Clean Water Wells');

        $this->get(route('wishlist.campaign', $campaign));
        $this->withCookie('locale', 'id')->post('/login', ['email' => 'user@infaqin.test', 'password' => 'Donor#12345'])
            ->assertSessionHas('status', 'Disimpan ke wishlist');
    }

    public function test_drafts_cannot_be_saved(): void
    {
        $user = User::factory()->create();
        $draft = $this->campaign('Secret Draft Campaign', Campaign::STATUS_DRAFT);

        $this->actingAs($user)->postJson("/campaigns/{$draft->slug}/wishlist", ['saved' => true])->assertNotFound();
        $this->actingAs($user)->get("/campaigns/{$draft->slug}/wishlist")->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->get("/campaigns/{$draft->slug}/wishlist")->assertNotFound()->assertSessionMissing('wishlist.intent');

        $this->assertSame(0, $this->wishlistRows($user, $draft));
    }

    public function test_wishlist_page_shows_open_and_closed_campaigns_but_never_drafts(): void
    {
        $user = User::factory()->create();
        $open = $this->campaign('Clean Water Wells');
        $closed = $this->campaign('Finished Flood Relief', Campaign::STATUS_CLOSED);
        $draft = $this->campaign('Clinic Roof Repair');
        $notMine = $this->campaign('Someone Else Saved This');

        $user->wishlist()->attach([$open->id, $closed->id, $draft->id]);
        User::factory()->create()->wishlist()->attach($notMine);
        $draft->update(['status' => Campaign::STATUS_DRAFT]);

        $this->actingAs($user)->get('/wishlist')->assertOk()
            ->assertSee('Clean Water Wells')
            ->assertSee(route('donations.create', $open))
            ->assertSee('Finished Flood Relief')
            ->assertSee('data-campaign-closed', false)
            ->assertDontSee(route('donations.create', $closed))
            ->assertDontSee('Clinic Roof Repair')
            ->assertDontSee('Someone Else Saved This');
    }

    public function test_empty_wishlist_shows_a_hint(): void
    {
        $this->actingAs(User::factory()->create())->get('/wishlist')->assertOk()
            ->assertSee('Your wishlist is empty');
    }

    public function test_hearts_show_the_saved_state(): void
    {
        $user = User::factory()->create();
        $saved = $this->campaign('Clean Water Wells');
        $user->wishlist()->attach($saved);

        $this->actingAs($user)->get('/campaigns?q=Clean+Water+Wells')->assertOk()
            ->assertSee(route('wishlist.update', $saved))
            ->assertSee('aria-pressed="true"', false);

        $this->actingAs($user)->get("/campaigns/{$saved->slug}")->assertOk()
            ->assertSee('aria-pressed="true"', false);

        $unsaved = $this->campaign('Seeds for Farmers');

        $this->actingAs($user)->get('/campaigns?q=Seeds+for+Farmers')->assertOk()
            ->assertSee(route('wishlist.update', $unsaved))
            ->assertSee('aria-pressed="false"', false)
            ->assertDontSee('aria-pressed="true"', false);
    }

    public function test_navbar_and_home_link_to_the_wishlist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/home')->assertOk()
            ->assertSee('href="'.route('wishlist').'"', false);
        $this->actingAs($user)->get('/campaigns')->assertOk()
            ->assertSee('href="'.route('wishlist').'"', false);
    }
}
