<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Campaigner;
use App\Models\Category;
use App\Models\Donation;
use App\Models\News;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(array $attributes = []): Campaign
    {
        $campaigner = Campaigner::firstOrCreate(['slug' => 'schema-test-foundation'], ['name' => 'Test Foundation']);

        return Campaign::create($attributes + [
            'campaigner_id' => $campaigner->id,
            'category_id' => Category::where('slug', 'health')->value('id'),
            'title' => 'Help the village',
            'slug' => 'schema-test-campaign',
            'summary' => 'Summary',
            'description' => 'Description',
            'target_amount' => 10_000_000,
            'status' => Campaign::STATUS_ACTIVE,
        ]);
    }

    public function test_categories_are_seeded_by_the_migration(): void
    {
        $this->assertEqualsCanonicalizing(
            ['education', 'mosque', 'quran', 'health', 'disaster', 'orphans'],
            Category::pluck('slug')->all()
        );
    }

    public function test_campaign_belongs_to_a_category(): void
    {
        $campaign = $this->campaign();

        $this->assertSame('Health', $campaign->category->name);
        $this->assertSame('Health', $campaign->categoryLabel());
        $this->assertTrue(Category::where('slug', 'health')->first()->campaigns->contains($campaign));
    }

    public function test_a_category_in_use_cannot_be_deleted(): void
    {
        $campaign = $this->campaign();

        try {
            $campaign->category->delete();
            $this->fail('Deleting a category that is still used by a campaign should fail.');
        } catch (QueryException) {
            $this->assertModelExists($campaign->category);
        }

        $unused = Category::create(['name' => 'Unused', 'slug' => 'unused']);
        $unused->delete();
        $this->assertModelMissing($unused);
    }

    public function test_new_columns_have_their_defaults(): void
    {
        $campaigner = Campaigner::create(['name' => 'Partner', 'slug' => 'schema-test-partner']);
        $user = User::factory()->create();

        $this->assertFalse($campaigner->fresh()->is_internal);
        $this->assertTrue($user->fresh()->is_active);
        $this->assertTrue(Campaigner::create(['name' => 'Internal', 'slug' => 'schema-test-internal', 'is_internal' => true])->fresh()->is_internal);
    }

    public function test_is_active_is_not_mass_assignable(): void
    {
        $user = User::factory()->create();
        $user->update(['is_active' => false]);

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_news_can_belong_to_a_campaign_and_is_kept_when_it_is_deleted(): void
    {
        $campaign = $this->campaign();
        $news = News::create([
            'campaign_id' => $campaign->id, 'title' => 'Update', 'slug' => 'schema-test-update',
            'category' => 'Infaqin', 'excerpt' => 'Excerpt', 'body' => 'Body',
        ]);

        $this->assertTrue($news->campaign->is($campaign));
        $this->assertTrue($campaign->news->contains($news));

        $campaign->delete();

        $this->assertNull($news->fresh()->campaign_id);
    }

    public function test_a_campaign_can_be_wishlisted_once_per_user(): void
    {
        $campaign = $this->campaign();
        $user = User::factory()->create();

        $user->wishlist()->attach($campaign);

        $this->assertTrue($user->wishlist->contains($campaign));
        $this->assertTrue($campaign->wishlistedBy->contains($user));
        $this->assertNotNull($user->wishlist->first()->pivot->created_at);

        $this->expectException(QueryException::class);
        $user->wishlist()->attach($campaign);
    }

    public function test_donations_have_an_expired_status_and_a_snap_token_column(): void
    {
        $this->assertContains(Donation::STATUS_EXPIRED, Donation::STATUSES);

        $donation = new Donation([
            'campaign_id' => $this->campaign()->id, 'amount' => 50_000, 'donor_name' => 'Donor',
            'email' => 'donor@example.com', 'payment_method' => 'qris',
        ]);
        $donation->status = Donation::STATUS_PENDING;
        $donation->snap_token = 'snap-token-123';
        $donation->save();

        $this->assertSame('snap-token-123', $donation->fresh()->snap_token);
    }

    public function test_campaign_statuses_are_draft_active_and_closed(): void
    {
        $this->assertSame(['draft', 'active', 'closed'], Campaign::STATUSES);
    }
}
