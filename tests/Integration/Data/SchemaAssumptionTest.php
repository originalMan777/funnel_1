<?php

namespace Tests\Integration\Data;

use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Models\Popup;
use App\Models\Post;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaAssumptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_critical_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('posts'));
        $this->assertTrue(Schema::hasTable('popups'));
        $this->assertTrue(Schema::hasTable('lead_boxes'));
        $this->assertTrue(Schema::hasTable('lead_slots'));
        $this->assertTrue(Schema::hasTable('lead_assignments'));
        $this->assertTrue(Schema::hasTable('leads'));
        $this->assertTrue(Schema::hasTable('popup_leads'));
        $this->assertTrue(Schema::hasTable('security_audit_logs'));

        $this->assertTrue(Schema::hasColumns('posts', ['slug', 'status', 'published_at', 'created_by', 'updated_by']));
        $this->assertTrue(Schema::hasColumns('popups', ['slug', 'is_active', 'target_pages', 'form_fields']));
        $this->assertTrue(Schema::hasColumns('leads', ['lead_box_id', 'lead_slot_key', 'payload']));
    }

    public function test_unique_constraints_for_post_and_popup_slugs_are_enforced(): void
    {
        $post = Post::factory()->create(['slug' => 'unique-post']);
        Popup::factory()->create(['slug' => 'unique-popup']);

        $this->assertNotNull($post->id);

        try {
            Post::factory()->create(['slug' => 'unique-post']);
            $this->fail('Expected duplicate post slug to fail.');
        } catch (QueryException $exception) {
            $this->assertTrue(true);
        }

        try {
            Popup::factory()->create(['slug' => 'unique-popup']);
            $this->fail('Expected duplicate popup slug to fail.');
        } catch (QueryException $exception) {
            $this->assertTrue(true);
        }
    }

    public function test_lead_assignments_remain_one_per_slot_and_leads_accept_nullable_box_and_slot(): void
    {
        $slot = LeadSlot::factory()->create();
        $firstBox = LeadBox::factory()->resource()->active()->create();
        $secondBox = LeadBox::factory()->resource()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $firstBox->id,
        ]);

        try {
            LeadAssignment::factory()->create([
                'lead_slot_id' => $slot->id,
                'lead_box_id' => $secondBox->id,
            ]);
            $this->fail('Expected duplicate lead slot assignment to fail.');
        } catch (QueryException $exception) {
            $this->assertTrue(true);
        }

        DB::table('leads')->insert([
            'lead_box_id' => null,
            'lead_slot_key' => null,
            'page_key' => 'schema-test',
            'source_url' => 'https://example.com',
            'type' => 'contact',
            'first_name' => 'Schema',
            'email' => 'schema@example.com',
            'payload' => json_encode(['message' => 'ok'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('leads', [
            'page_key' => 'schema-test',
            'email' => 'schema@example.com',
        ]);
    }
}
