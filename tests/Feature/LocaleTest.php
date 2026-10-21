<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_english_is_the_default(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('About us');
    }

    public function test_switching_language_remembers_the_choice(): void
    {
        $this->from('/faq')->get('/language/id')
            ->assertRedirect('/faq')
            ->assertCookie('locale', 'id');

        $this->withCookie('locale', 'id')->get('/')->assertOk()
            ->assertSee('<html lang="id">', false)
            ->assertSee('Tentang kami')
            ->assertSee('Kalkulator zakat')
            ->assertDontSee('About us');
    }

    public function test_browser_language_is_used_when_nothing_was_chosen(): void
    {
        $this->get('/', ['Accept-Language' => 'id-ID,id;q=0.9,en;q=0.8'])->assertSee('Tentang kami');
        $this->withCookie('locale', 'en')->get('/', ['Accept-Language' => 'id-ID'])->assertSee('About us');
    }

    public function test_unknown_language_is_rejected(): void
    {
        $this->get('/language/fr')->assertNotFound();
    }

    public function test_every_page_renders_in_indonesian(): void
    {
        $campaign = Campaign::first();
        $news = News::first();

        foreach (['/', '/login', '/register', '/campaigns', '/campaigns/'.$campaign->slug, '/news', '/news/'.$news->slug, '/terms', '/faq'] as $url) {
            $this->withCookie('locale', 'id')->get($url)->assertOk();
        }

        $this->withCookie('locale', 'id')->get('/faq')->assertSee('Apa itu Buku Kas Terbuka?');
        $this->withCookie('locale', 'id')->get('/terms')->assertSee('Pengembalian dana');

        $user = User::factory()->create();
        $donation = Donation::where('status', Donation::STATUS_PAID)->first();
        $donation?->forceFill(['user_id' => $user->id])->save();

        foreach (['/home', '/history', '/wishlist', '/profile', '/campaigns/'.$campaign->slug.'/donate'] as $url) {
            $this->actingAs($user)->withCookie('locale', 'id')->get($url)->assertOk();
        }
        if ($donation) {
            $this->actingAs($user)->withCookie('locale', 'id')->get('/donations/'.$donation->reference)->assertOk()->assertSee('Referensi');
        }
    }

    public function test_validation_messages_are_translated(): void
    {
        $this->withCookie('locale', 'id')->post('/login', ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email' => 'Email wajib diisi.']);
    }
}
