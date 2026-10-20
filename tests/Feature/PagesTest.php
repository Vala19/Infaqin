<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_public_pages_render(): void
    {
        $campaign = Campaign::first();
        $news = News::first();

        foreach (['/', '/login', '/register', '/campaigns', '/campaigns/'.$campaign->slug, '/news', '/news/'.$news->slug, '/terms', '/faq'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/')->assertSee('About us')->assertSee('Zakat calculator')->assertSee('Contact us');
    }

    public function test_terms_and_faq_pages_have_their_content(): void
    {
        $this->get('/terms')->assertOk()
            ->assertSee('Terms and Conditions')
            ->assertSee('id="privacy"', false)
            ->assertSee('id="refunds"', false);

        $this->get('/faq')->assertOk()
            ->assertSee('Frequently asked questions')
            ->assertSee('What is the Open Fund Ledger?')
            ->assertSee('"@type":"FAQPage"', false);

        $this->get('/register')->assertSee(route('terms'), false);
    }

    public function test_authenticated_pages_render(): void
    {
        $user = User::where('email', 'user@infaqin.test')->first();
        $campaign = Campaign::first();

        $this->actingAs($user);

        foreach (['/home', '/history', '/profile', '/campaigns/'.$campaign->slug.'/donate', '/campaigns/'.$campaign->slug.'/comments?page=2'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/home')->assertSee('Kindness streak')->assertSee('4 weeks');
    }

    public function test_guests_are_redirected_from_protected_pages(): void
    {
        foreach (['/home', '/history', '/profile', '/admin'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_campaign_search_filter_and_sort(): void
    {
        $this->get('/campaigns?q=Qur')->assertOk()->assertSee('Sebar Sejuta')->assertDontSee('Mobile Clinic for Remote');
        $this->get('/campaigns?category[]=health')->assertOk()->assertSee('Mobile Clinic')->assertDontSee('GEMAS');
        $this->get('/campaigns?urgent=1')->assertOk()->assertSee('GEMAS')->assertDontSee('Scholarships for 100 Orphans');

        foreach (array_keys(\App\Http\Controllers\CampaignController::SORTS) as $sort) {
            $this->get('/campaigns?sort='.$sort)->assertOk();
        }

        $this->get('/campaigns?sort=drop_table')->assertSessionHasErrors('sort');
    }

    public function test_history_search_filter_and_sort(): void
    {
        $this->actingAs(User::where('email', 'user@infaqin.test')->first());

        $this->get('/history?status=pending')->assertOk()->assertSee('Pay now')->assertDontSee('Receipt');
        $this->get('/history?q=GEMAS')->assertOk()->assertSee('GEMAS');
        $this->get('/history?sort=amount_desc&method=qris&from=2020-01-01&to='.now()->toDateString())->assertOk();
    }

    public function test_unknown_page_uses_branded_404(): void
    {
        $this->get('/does-not-exist')->assertNotFound()->assertSee('Page not found');
    }

    public function test_security_headers_are_present(): void
    {
        $this->get('/')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
