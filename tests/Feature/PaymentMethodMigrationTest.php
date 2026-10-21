<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentMethodMigrationTest extends TestCase
{
    use DatabaseMigrations;

    private array $sharedConnections = [];

    private bool $sharedMigrated = false;

    /**
     * Same isolation as CategoryMigrationTest: a fresh in-memory database that
     * does not disturb the seeded connection other test classes reuse.
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

    private function paymentMethodIsNullable(): bool
    {
        return collect(Schema::getColumns('donations'))->firstWhere('name', 'payment_method')['nullable'];
    }

    public function test_making_payment_method_nullable_keeps_donations_and_their_prayers(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 1])->assertSuccessful();
        $this->assertFalse($this->paymentMethodIsNullable());
        $this->assertSame(1, (int) DB::scalar('pragma foreign_keys'));

        $now = now();
        $userId = DB::table('users')->insertGetId(['name' => 'Donor', 'email' => 'donor@example.com', 'password' => 'x', 'created_at' => $now, 'updated_at' => $now]);
        $campaignerId = DB::table('campaigners')->insertGetId(['name' => 'Test', 'slug' => 'test', 'created_at' => $now, 'updated_at' => $now]);
        $campaignId = DB::table('campaigns')->insertGetId([
            'campaigner_id' => $campaignerId, 'category_id' => DB::table('categories')->value('id'),
            'title' => 'Campaign', 'slug' => 'campaign', 'summary' => 'Summary', 'description' => 'Description',
            'target_amount' => 10_000_000, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);

        foreach (['INF-AAAAAAAAAA' => 'qris', 'INF-BBBBBBBBBB' => 'bca_va', 'INF-CCCCCCCCCC' => 'gopay'] as $reference => $method) {
            $donationId = DB::table('donations')->insertGetId([
                'reference' => $reference, 'user_id' => $userId, 'campaign_id' => $campaignId, 'amount' => 50_000,
                'donor_name' => 'Donor', 'email' => 'donor@example.com', 'payment_method' => $method,
                'status' => 'paid', 'paid_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('comments')->insert([
                'campaign_id' => $campaignId, 'user_id' => $userId, 'donation_id' => $donationId,
                'body' => 'Semoga berkah', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $before = DB::table('comments')->join('donations', 'donations.id', '=', 'comments.donation_id')
            ->orderBy('reference')->pluck('reference', 'comments.id')->all();

        $this->artisan('migrate')->assertSuccessful();

        $this->assertTrue($this->paymentMethodIsNullable());
        $this->assertSame(3, DB::table('donations')->count());
        $this->assertSame(3, DB::table('comments')->count());
        $this->assertSame($before, DB::table('comments')->join('donations', 'donations.id', '=', 'comments.donation_id')
            ->orderBy('reference')->pluck('reference', 'comments.id')->all());
        $this->assertSame(['qris', 'bca_va', 'gopay'], DB::table('donations')->orderBy('reference')->pluck('payment_method')->all());

        // The foreign key still points at donations, still cascades, and nothing dangles.
        $this->assertSame(1, (int) DB::scalar('pragma foreign_keys'));
        $this->assertSame([], DB::select('pragma foreign_key_check'));
        $foreignKey = collect(Schema::getForeignKeys('comments'))->first(fn ($fk) => $fk['columns'] === ['donation_id']);
        $this->assertSame('donations', $foreignKey['foreign_table']);
        $this->assertSame('cascade', strtolower($foreignKey['on_delete']));

        DB::table('donations')->insert([
            'reference' => 'INF-DDDDDDDDDD', 'campaign_id' => $campaignId, 'amount' => 50_000, 'donor_name' => 'Donor',
            'email' => 'donor@example.com', 'payment_method' => null, 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
        ]);

        $this->artisan('migrate:rollback', ['--step' => 1])->assertSuccessful();

        $this->assertFalse($this->paymentMethodIsNullable());
        $this->assertSame(4, DB::table('donations')->count());
        $this->assertSame(3, DB::table('comments')->count());
        $this->assertSame([], DB::select('pragma foreign_key_check'));
    }
}
