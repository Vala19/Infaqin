<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryMigrationTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Migrations added on 2026-10-20 after create_categories_table.
     */
    private const STEPS_AFTER_CATEGORIES = 7;

    private array $sharedConnections = [];

    private bool $sharedMigrated = false;

    /**
     * Use a fresh in-memory database, and restore RefreshDatabase's shared state
     * afterwards, so this test does not wipe or re-migrate the seeded connection
     * that other test classes reuse.
     */
    protected function setUp(): void
    {
        $this->sharedConnections = RefreshDatabaseState::$inMemoryConnections;
        $this->sharedMigrated = RefreshDatabaseState::$migrated;
        RefreshDatabaseState::$inMemoryConnections = [];

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        RefreshDatabaseState::$inMemoryConnections = $this->sharedConnections;
        RefreshDatabaseState::$migrated = $this->sharedMigrated;
    }

    private function insertCampaign(string $slug, string $category, string $status): void
    {
        DB::table('campaigns')->insert([
            'campaigner_id' => DB::table('campaigners')->value('id'),
            'title' => $slug,
            'slug' => $slug,
            'category' => $category,
            'summary' => 'Summary',
            'description' => 'Description',
            'target_amount' => 10_000_000,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_string_categories_are_moved_to_category_id_and_back(): void
    {
        $this->artisan('migrate:rollback', ['--step' => self::STEPS_AFTER_CATEGORIES])->assertSuccessful();
        $this->assertTrue(Schema::hasColumn('campaigns', 'category'));

        DB::table('campaigners')->insert(['name' => 'Test', 'slug' => 'test', 'created_at' => now(), 'updated_at' => now()]);
        $this->insertCampaign('known', 'health', 'active');
        $this->insertCampaign('unknown', 'water', 'completed');

        $this->artisan('migrate')->assertSuccessful();

        $this->assertFalse(Schema::hasColumn('campaigns', 'category'));
        $this->assertSame(
            DB::table('categories')->where('slug', 'health')->value('id'),
            DB::table('campaigns')->where('slug', 'known')->value('category_id')
        );
        $this->assertSame('Water', DB::table('categories')->where('slug', 'water')->value('name'));
        $this->assertSame(
            DB::table('categories')->where('slug', 'water')->value('id'),
            DB::table('campaigns')->where('slug', 'unknown')->value('category_id')
        );
        $this->assertSame('closed', DB::table('campaigns')->where('slug', 'unknown')->value('status'));

        $this->artisan('migrate:rollback', ['--step' => self::STEPS_AFTER_CATEGORIES])->assertSuccessful();

        $this->assertFalse(Schema::hasColumn('campaigns', 'category_id'));
        $this->assertSame(['known' => 'health', 'unknown' => 'water'], DB::table('campaigns')->orderBy('slug')->pluck('category', 'slug')->all());
    }
}
