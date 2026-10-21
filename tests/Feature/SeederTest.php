<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_seeder_creates_a_draft_campaign_hidden_from_the_public(): void
    {
        $draft = Campaign::where('slug', 'ramadan-iftar-for-pesantren-students')->firstOrFail();

        $this->assertSame(Campaign::STATUS_DRAFT, $draft->status);
        $this->assertSame(0, $draft->collected_amount);

        $this->get('/campaigns/ramadan-iftar-for-pesantren-students')->assertNotFound();
        $this->get('/campaigns?status=all')->assertOk()->assertDontSee('Ramadan Iftar for Pesantren Students');

        $admin = User::where('email', 'admin@infaqin.test')->firstOrFail();
        $this->actingAs($admin)->get('/admin/campaigns')->assertOk()->assertSee('Ramadan Iftar for Pesantren Students');
    }
}
