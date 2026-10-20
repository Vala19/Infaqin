<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Campaigner;
use App\Models\Category;
use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        return User::where('email', 'admin@infaqin.test')->firstOrFail();
    }

    public function test_non_admins_are_forbidden(): void
    {
        $this->actingAs(User::where('email', 'user@infaqin.test')->first());

        $this->get('/admin')->assertForbidden();
        $this->post('/admin/news', [])->assertForbidden();
    }

    public function test_admin_pages_render(): void
    {
        $this->actingAs($this->admin());
        $campaign = Campaign::first();
        $news = News::first();

        foreach (['/admin', '/admin/news', '/admin/news/create', "/admin/news/{$news->slug}/edit", '/admin/campaigns', '/admin/campaigns/create', "/admin/campaigns/{$campaign->slug}/edit", '/admin/donations'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_can_manage_news(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->post('/admin/news', [
            'title' => 'Aid reaches the coast',
            'category' => 'World',
            'excerpt' => 'Short summary',
            'body' => "First paragraph.\n\nSecond <script>alert(1)</script> paragraph.",
            'published_at' => now()->subMinute()->format('Y-m-d\TH:i'),
            'image' => UploadedFile::fake()->createWithContent('cover.jpg', file_get_contents(public_path('images/campaigns/handover.jpg'))),
        ])->assertRedirect('/admin/news');

        $article = News::where('slug', 'aid-reaches-the-coast')->firstOrFail();
        $this->assertStringNotContainsString('<script>', $article->body);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $article->image));

        $this->get('/news/aid-reaches-the-coast')->assertOk()->assertSee('Second');

        $this->delete("/admin/news/{$article->slug}")->assertRedirect('/admin/news');
        $this->assertModelMissing($article);
    }

    public function test_campaign_form_requires_an_existing_category(): void
    {
        $this->actingAs($this->admin());
        $data = [
            'campaigner_id' => Campaigner::first()->id,
            'title' => 'New Water Project',
            'summary' => 'Summary',
            'description' => 'Description',
            'target_amount' => 10_000_000,
            'status' => Campaign::STATUS_DRAFT,
        ];

        $this->post('/admin/campaigns', $data)->assertSessionHasErrors('category_id');
        $this->post('/admin/campaigns', $data + ['category_id' => 999])->assertSessionHasErrors('category_id');
        $this->assertDatabaseMissing('campaigns', ['title' => 'New Water Project']);

        $category = Category::where('slug', 'health')->firstOrFail();
        $this->post('/admin/campaigns', $data + ['category_id' => $category->id])->assertSessionHasNoErrors();

        $campaign = Campaign::where('title', 'New Water Project')->firstOrFail();
        $this->assertTrue($campaign->category->is($category));
        $this->assertSame(Campaign::STATUS_DRAFT, $campaign->status);

        $this->put("/admin/campaigns/{$campaign->slug}", $data + ['category_id' => ''])->assertSessionHasErrors('category_id');
        $this->assertTrue($campaign->fresh()->category->is($category));
    }

    public function test_admin_can_record_withdrawals_within_available_funds(): void
    {
        $this->actingAs($this->admin());
        $campaign = Campaign::first();
        $available = $campaign->collected_amount - $campaign->withdrawals()->sum('amount');

        $this->post("/admin/campaigns/{$campaign->slug}/withdrawals", [
            'amount' => $available + 1, 'description' => 'Too much', 'withdrawn_at' => now()->subMinute()->toDateTimeString(),
        ])->assertSessionHasErrorsIn('withdrawal', 'amount');

        $this->post("/admin/campaigns/{$campaign->slug}/withdrawals", [
            'amount' => 1000, 'description' => 'Buying supplies', 'withdrawn_at' => now()->subMinute()->toDateTimeString(),
        ])->assertSessionHasNoErrors();

        $this->get("/campaigns/{$campaign->slug}")->assertSee('Buying supplies');
    }
}
