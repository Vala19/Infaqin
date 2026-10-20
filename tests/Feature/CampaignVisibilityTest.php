<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Campaigner;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function campaign(string $status, string $title): Campaign
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
            'is_featured' => true,
            'is_urgent' => true,
            'status' => $status,
        ]);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@infaqin.test')->firstOrFail();
    }

    public function test_drafts_are_hidden_from_guests(): void
    {
        $draft = $this->campaign(Campaign::STATUS_DRAFT, 'Secret Draft Campaign');

        $this->get("/campaigns/{$draft->slug}")->assertNotFound();
        $this->get("/campaigns/{$draft->slug}/comments")->assertNotFound();

        foreach (['/', '/campaigns', '/campaigns?status=all', '/campaigns?status=closed', '/campaigns?category[]=health', '/campaigns?status=all&q=Secret', '/campaigns?status=all&category[]=health&q=Secret'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('Secret Draft Campaign');
        }
    }

    public function test_drafts_are_hidden_from_donors(): void
    {
        $draft = $this->campaign(Campaign::STATUS_DRAFT, 'Secret Draft Campaign');
        $this->actingAs(User::where('email', 'user@infaqin.test')->firstOrFail());

        $this->get("/campaigns/{$draft->slug}")->assertNotFound();
        $this->get("/campaigns/{$draft->slug}/donate")->assertNotFound();
        $this->post("/campaigns/{$draft->slug}/donate", [
            'amount' => 50_000, 'email' => 'user@infaqin.test', 'payment_method' => 'qris',
        ])->assertNotFound();
        $this->post("/campaigns/{$draft->slug}/comments", ['body' => 'Aamiin ya Allah'])->assertNotFound();
        $this->assertSame(0, $draft->donations()->count());

        foreach (['/home', '/campaigns?status=all', '/campaigns?status=all&q=Secret'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('Secret Draft Campaign');
        }

        // Related campaigns on a sibling's page in the same category.
        $sibling = Campaign::where('category_id', $draft->category_id)->where('status', Campaign::STATUS_ACTIVE)->firstOrFail();
        $this->get("/campaigns/{$sibling->slug}")->assertOk()->assertDontSee('Secret Draft Campaign');
    }

    public function test_admins_can_see_and_edit_drafts(): void
    {
        $draft = $this->campaign(Campaign::STATUS_DRAFT, 'Secret Draft Campaign');
        $this->actingAs($this->admin());

        $this->get('/admin/campaigns')->assertOk()->assertSee('Secret Draft Campaign');
        $this->get("/admin/campaigns/{$draft->slug}/edit")->assertOk()->assertSee('Secret Draft Campaign');
    }

    public function test_closed_campaigns_stay_visible_without_a_donate_button(): void
    {
        $closed = $this->campaign(Campaign::STATUS_CLOSED, 'Finished Campaign');

        $this->get("/campaigns/{$closed->slug}")->assertOk()
            ->assertSee('Finished Campaign')
            ->assertDontSee(route('donations.create', $closed));
        $this->get('/campaigns?status=closed&q=Finished')->assertOk()->assertSee('Finished Campaign');
        $this->get('/campaigns?status=all&q=Finished')->assertOk()->assertSee('Finished Campaign');

        $this->actingAs(User::where('email', 'user@infaqin.test')->firstOrFail());
        $this->get("/campaigns/{$closed->slug}/donate")->assertRedirect("/campaigns/{$closed->slug}");
    }

    public function test_the_old_completed_filter_is_rejected(): void
    {
        $this->get('/campaigns?status=completed')->assertRedirect();
    }
}
