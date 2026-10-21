<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Campaigner;
use App\Models\Category;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CloseCampaignsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(string $slug, ?string $deadline, string $status = Campaign::STATUS_ACTIVE): Campaign
    {
        $campaigner = Campaigner::firstOrCreate(['slug' => 'test-foundation'], ['name' => 'Test Foundation']);

        return Campaign::create([
            'campaigner_id' => $campaigner->id,
            'category_id' => Category::where('slug', 'health')->value('id'),
            'title' => $slug,
            'slug' => $slug,
            'summary' => 'Summary',
            'description' => 'Description',
            'target_amount' => 10_000_000,
            'deadline' => $deadline,
            'status' => $status,
        ]);
    }

    public function test_it_closes_only_active_campaigns_past_their_deadline(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 21)->startOfDay());

        $overdue = $this->campaign('overdue', '2026-10-20');
        $longOverdue = $this->campaign('long-overdue', '2026-01-01');
        $endsToday = $this->campaign('ends-today', '2026-10-21');
        $future = $this->campaign('future', '2026-11-30');
        $noDeadline = $this->campaign('no-deadline', null);
        $draft = $this->campaign('draft', '2026-10-01', Campaign::STATUS_DRAFT);

        $this->artisan('campaigns:close')
            ->expectsOutput('Closed 2 campaign(s) past their deadline.')
            ->assertSuccessful();

        $this->assertSame(Campaign::STATUS_CLOSED, $overdue->fresh()->status);
        $this->assertSame(Campaign::STATUS_CLOSED, $longOverdue->fresh()->status);
        $this->assertSame(Campaign::STATUS_ACTIVE, $endsToday->fresh()->status);
        $this->assertSame(Campaign::STATUS_ACTIVE, $future->fresh()->status);
        $this->assertSame(Campaign::STATUS_ACTIVE, $noDeadline->fresh()->status);
        $this->assertSame(Campaign::STATUS_DRAFT, $draft->fresh()->status);
    }

    public function test_campaign_closes_on_the_run_after_its_deadline_day(): void
    {
        $campaign = $this->campaign('closing', '2026-10-21');

        $this->travelTo(now()->setDate(2026, 10, 21)->setTime(23, 59));
        $this->artisan('campaigns:close')->assertSuccessful();
        $this->assertTrue($campaign->fresh()->isOpen());

        $this->travelTo(now()->setDate(2026, 10, 22)->startOfDay());
        $this->artisan('campaigns:close')->assertSuccessful();
        $this->assertSame(Campaign::STATUS_CLOSED, $campaign->fresh()->status);
        $this->assertFalse($campaign->fresh()->isOpen());
    }

    public function test_closed_campaign_stays_public_without_donations(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 21)->startOfDay());
        $campaign = $this->campaign('was-open', '2026-10-20');

        $this->artisan('campaigns:close')->assertSuccessful();

        $this->get("/campaigns/{$campaign->slug}")->assertOk();
        $this->get('/campaigns?status=closed&q=was-open')->assertOk()->assertSee('was-open');
    }

    public function test_close_command_is_scheduled_daily_at_midnight(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'campaigns:close'));

        $this->assertNotNull($event);
        $this->assertSame('0 0 * * *', $event->expression);
    }

    public function test_close_command_runs_at_midnight_wib_not_utc(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'campaigns:close'));

        $this->assertSame('Asia/Jakarta', $event->timezone);

        // 00:00 WIB on 22 Oct is still 21 Oct 17:00 UTC.
        $this->travelTo(Carbon::parse('2026-10-21 17:00:00', 'UTC'));
        $this->assertTrue($event->isDue(app()));

        $this->travelTo(Carbon::parse('2026-10-22 00:00:00', 'UTC'));
        $this->assertFalse($event->isDue(app()));
    }

    public function test_deadline_day_ends_at_midnight_wib(): void
    {
        $campaign = $this->campaign('wib-deadline', '2026-10-21');

        $this->travelTo(Carbon::parse('2026-10-21 17:00:00', 'UTC'));
        $this->artisan('campaigns:close')->assertSuccessful();

        $this->assertSame(Campaign::STATUS_CLOSED, $campaign->fresh()->status);
    }
}
