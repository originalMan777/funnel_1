<?php

namespace Tests\Integration\LeadSlots;

use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Services\LeadSlots\LeadSlotResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadSlotResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_resolution_keeps_all_home_slots_even_if_page_slot_config_is_stale(): void
    {
        config()->set('lead_blocks.page_slots.home', ['home_intro']);

        $resolved = app(LeadSlotResolver::class)->resolve('home');

        $this->assertArrayHasKey('home_intro', $resolved);
        $this->assertArrayHasKey('home_mid', $resolved);
        $this->assertArrayHasKey('home_bottom', $resolved);
    }

    public function test_resolver_presents_assignment_overrides_for_active_compatible_slots(): void
    {
        $slot = LeadSlot::factory()->create([
            'key' => 'home_intro',
            'is_enabled' => true,
        ]);

        $box = LeadBox::factory()->resource()->active()->create([
            'title' => 'Base Title',
            'short_text' => 'Base short text',
            'button_text' => 'Base CTA',
        ]);

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $box->id,
            'override_title' => 'Override Title',
            'override_short_text' => 'Override short',
            'override_button_text' => 'Override CTA',
        ]);

        $resolved = app(LeadSlotResolver::class)->resolve('home');

        $this->assertSame('Override Title', $resolved['home_intro']['title']);
        $this->assertSame('Override short', $resolved['home_intro']['shortText']);
        $this->assertSame('Override CTA', $resolved['home_intro']['buttonText']);
        $this->assertSame('home', $resolved['home_intro']['context']['pageKey']);
    }

    public function test_resolver_returns_null_for_missing_disabled_or_inactive_slots(): void
    {
        $disabledSlot = LeadSlot::factory()->create([
            'key' => 'home_intro',
            'is_enabled' => false,
        ]);
        $resourceBox = LeadBox::factory()->resource()->active()->create();
        LeadAssignment::factory()->create([
            'lead_slot_id' => $disabledSlot->id,
            'lead_box_id' => $resourceBox->id,
        ]);

        $inactiveSlot = LeadSlot::factory()->create([
            'key' => 'home_mid',
            'is_enabled' => true,
        ]);
        $inactiveBox = LeadBox::factory()->service()->create([
            'status' => LeadBox::STATUS_INACTIVE,
        ]);
        LeadAssignment::factory()->create([
            'lead_slot_id' => $inactiveSlot->id,
            'lead_box_id' => $inactiveBox->id,
        ]);

        $activeSlot = LeadSlot::factory()->create([
            'key' => 'home_bottom',
            'is_enabled' => true,
        ]);
        $activeBox = LeadBox::factory()->service()->active()->create();
        LeadAssignment::factory()->create([
            'lead_slot_id' => $activeSlot->id,
            'lead_box_id' => $activeBox->id,
        ]);

        $resolved = app(LeadSlotResolver::class)->resolve('home');

        $this->assertNull($resolved['home_intro']);
        $this->assertNull($resolved['home_mid']);
        $this->assertSame($activeBox->id, $resolved['home_bottom']['leadBoxId']);
    }

    public function test_same_lead_box_can_resolve_in_multiple_slots_without_being_deduped(): void
    {
        $leadBox = LeadBox::factory()->offer()->active()->create([
            'title' => 'Reusable Offer Box',
        ]);

        foreach ([
            'blog_index_mid_lead',
            'blog_post_inline_1',
            'blog_post_inline_2',
            'blog_post_inline_3',
            'blog_post_inline_4',
            'blog_post_before_related',
        ] as $slotKey) {
            $slot = LeadSlot::factory()->create([
                'key' => $slotKey,
                'is_enabled' => true,
            ]);

            LeadAssignment::factory()->create([
                'lead_slot_id' => $slot->id,
                'lead_box_id' => $leadBox->id,
            ]);
        }

        $blogIndexResolved = app(LeadSlotResolver::class)->resolve('blog_index');
        $blogShowResolved = app(LeadSlotResolver::class)->resolve('blog_show');

        $this->assertSame($leadBox->id, $blogIndexResolved['blog_index_mid_lead']['leadBoxId']);

        foreach ([
            'blog_post_inline_1',
            'blog_post_inline_2',
            'blog_post_inline_3',
            'blog_post_inline_4',
            'blog_post_before_related',
        ] as $slotKey) {
            $this->assertSame($leadBox->id, $blogShowResolved[$slotKey]['leadBoxId']);
            $this->assertSame($slotKey, $blogShowResolved[$slotKey]['context']['slotKey']);
        }
    }
}
