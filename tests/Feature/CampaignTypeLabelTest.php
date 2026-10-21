<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Campaigner;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTypeLabelTest extends TestCase
{
    use RefreshDatabase;

    private Campaign $program;

    private Campaign $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $syaamil = Campaigner::create(['name' => 'Syaamil Internal', 'slug' => 'syaamil-internal', 'is_verified' => true, 'is_internal' => true]);
        $mitra = Campaigner::create(['name' => 'Mitra Foundation', 'slug' => 'mitra-foundation', 'is_verified' => true]);

        // Different categories, so neither shows up under the other's related campaigns.
        $this->program = $this->campaign($syaamil, 'Internal Quran Programme', 'quran');
        $this->partner = $this->campaign($mitra, 'Partner Water Wells', 'health');
    }

    private function campaign(Campaigner $campaigner, string $title, string $category): Campaign
    {
        return Campaign::create([
            'campaigner_id' => $campaigner->id,
            'category_id' => Category::where('slug', $category)->value('id'),
            'title' => $title,
            'slug' => str($title)->slug(),
            'summary' => 'Summary',
            'description' => 'Description',
            'target_amount' => 10_000_000,
            'deadline' => now()->addMonth()->toDateString(),
            'status' => Campaign::STATUS_ACTIVE,
        ]);
    }

    private function typeChip(string $label, string $where = 'card'): string
    {
        return 'data-campaign-type="'.$where.'">'.$label.'</span>';
    }

    public function test_type_label_follows_is_internal(): void
    {
        $this->assertTrue($this->program->isProgram());
        $this->assertSame('Program', $this->program->typeLabel());
        $this->assertFalse($this->partner->isProgram());
        $this->assertSame('Campaign', $this->partner->typeLabel());
    }

    public function test_campaign_cards_show_the_type_label(): void
    {
        $this->get('/campaigns?q=Internal+Quran')->assertOk()
            ->assertSee('Internal Quran Programme')
            ->assertSee($this->typeChip('Program'), false)
            ->assertDontSee($this->typeChip('Campaign'), false);

        $this->get('/campaigns?q=Partner+Water')->assertOk()
            ->assertSee('Partner Water Wells')
            ->assertSee($this->typeChip('Campaign'), false)
            ->assertDontSee($this->typeChip('Program'), false);
    }

    public function test_detail_page_shows_the_type_label(): void
    {
        $this->get("/campaigns/{$this->program->slug}")->assertOk()
            ->assertSee($this->typeChip('Program', 'hero'), false)
            ->assertDontSee($this->typeChip('Campaign', 'hero'), false);

        $this->get("/campaigns/{$this->partner->slug}")->assertOk()
            ->assertSee($this->typeChip('Campaign', 'hero'), false)
            ->assertDontSee($this->typeChip('Program', 'hero'), false);
    }

    public function test_list_can_be_filtered_by_type(): void
    {
        $this->get('/campaigns?type=program')->assertOk()
            ->assertSee($this->typeChip('Program'), false)
            ->assertDontSee($this->typeChip('Campaign'), false)
            ->assertSee('name="type" value="program" class="peer sr-only" checked', false);

        $this->get('/campaigns?type=campaign')->assertOk()
            ->assertSee($this->typeChip('Campaign'), false)
            ->assertDontSee($this->typeChip('Program'), false);

        $this->get('/campaigns?type=program&q=Internal+Quran')->assertOk()->assertSee('Internal Quran Programme');
        $this->get('/campaigns?type=program&q=Partner+Water')->assertOk()->assertDontSee('Partner Water Wells');
        $this->get('/campaigns?type=campaign&q=Partner+Water')->assertOk()->assertSee('Partner Water Wells');
        $this->get('/campaigns?type=campaign&q=Internal+Quran')->assertOk()->assertDontSee('Internal Quran Programme');

        $this->get('/campaigns?type=&status=all')->assertOk()
            ->assertSee($this->typeChip('Program'), false)
            ->assertSee($this->typeChip('Campaign'), false);
    }

    public function test_invalid_type_filter_is_rejected(): void
    {
        $this->get('/campaigns?type=secret')->assertSessionHasErrors('type');
    }

    public function test_labels_are_translated_in_indonesian(): void
    {
        $this->withCookie('locale', 'id');

        $this->get("/campaigns/{$this->partner->slug}")->assertOk()->assertSee($this->typeChip('Kampanye', 'hero'), false);
        $this->get('/campaigns')->assertOk()
            ->assertSee($this->typeChip('Program'), false)
            ->assertSee('Jenis');
    }
}
