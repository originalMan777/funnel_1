<?php

namespace Tests\Integration\Data;

use App\Models\LeadSlot;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LeadBlocksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(public_path('images/blog'));
    }

    public function test_lead_blocks_seeder_is_idempotent(): void
    {
        $this->seed(LeadBlocksSeeder::class);
        $this->seed(LeadBlocksSeeder::class);

        $this->assertDatabaseCount('lead_slots', 3);
        $this->assertDatabaseCount('lead_boxes', 3);
        $this->assertDatabaseHas('lead_boxes', ['internal_name' => 'Default Resource Lead Box']);
        $this->assertDatabaseHas('lead_boxes', ['internal_name' => 'Default Service Lead Box']);
        $this->assertDatabaseHas('lead_boxes', ['internal_name' => 'Default Offer Lead Box']);
    }

    public function test_database_seeder_can_run_twice_without_duplicate_baseline_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'test@example.com')->count());
        $this->assertSame(20, Post::query()->count());
        $this->assertSame(3, LeadSlot::query()->count());
    }
}
