<?php

namespace Tests\Integration\Data;

use App\Models\LeadSlot;
use App\Models\Post;
use Database\Seeders\AcquisitionCatalogSeeder;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LeadBlocksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\UsesIsolatedMediaRoot;
use Tests\TestCase;

class SeederSafetyTest extends TestCase
{
    use RefreshDatabase;
    use UsesIsolatedMediaRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpIsolatedMediaRoot();

        File::ensureDirectoryExists($this->isolatedBlogImagesRoot());
    }

    public function test_lead_blocks_seeder_is_idempotent(): void
    {
        $this->seed(LeadBlocksSeeder::class);
        $this->seed(LeadBlocksSeeder::class);

        $expectedSlotCount = count(config('lead_blocks.slot_keys', []));

        $this->assertDatabaseCount('lead_slots', $expectedSlotCount);
        $this->assertDatabaseCount('lead_boxes', 3);
        $this->assertDatabaseHas('lead_boxes', ['internal_name' => 'Default Resource Lead Box']);
        $this->assertDatabaseHas('lead_boxes', ['internal_name' => 'Default Service Lead Box']);
        $this->assertDatabaseHas('lead_boxes', ['internal_name' => 'Default Offer Lead Box']);
    }

    public function test_acquisition_catalog_seeder_is_idempotent(): void
    {
        $this->seed(AcquisitionCatalogSeeder::class);
        $this->seed(AcquisitionCatalogSeeder::class);

        $this->assertDatabaseCount('acquisitions', 3);
        $this->assertDatabaseCount('services', 6);
        $this->assertDatabaseCount('acquisition_paths', 4);
        $this->assertDatabaseHas('acquisition_paths', ['path_key' => 'seller.home-valuation.blog-inline']);
        $this->assertDatabaseHas('acquisition_paths', ['path_key' => 'general.contact.home-popup']);
    }

    public function test_database_seeder_can_run_twice_without_duplicate_baseline_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $expectedSlotCount = count(config('lead_blocks.slot_keys', []));

        $this->assertSame(1, User::query()->where('email', 'test@example.com')->count());
        $this->assertSame(20, Post::query()->count());
        $this->assertSame($expectedSlotCount, LeadSlot::query()->count());
        $this->assertDatabaseCount('acquisitions', 3);
        $this->assertDatabaseCount('services', 6);
        $this->assertDatabaseCount('acquisition_paths', 4);
    }
}
