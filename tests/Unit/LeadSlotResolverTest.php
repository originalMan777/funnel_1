<?php

namespace Tests\Unit;

use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Services\LeadSlots\LeadBoxPresenter;
use App\Services\LeadSlots\LeadSlotResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LeadSlotResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_valid_slot_correctly(): void
    {
        $slot = LeadSlot::factory()->create([
            'key' => 'home_intro',
            'is_enabled' => true,
        ]);
        $leadBox = LeadBox::factory()->resource()->active()->create([
            'title' => 'Resolver Title',
        ]);

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $leadBox->id,
        ]);

        $resolved = app(LeadSlotResolver::class)->resolve('home');

        $this->assertSame($leadBox->id, $resolved['home_intro']['leadBoxId']);
        $this->assertSame('Resolver Title', $resolved['home_intro']['title']);
        $this->assertSame('home_intro', $resolved['home_intro']['context']['slotKey']);
        $this->assertSame('home', $resolved['home_intro']['context']['pageKey']);
    }

    public function test_returns_null_if_slot_disabled(): void
    {
        LeadSlot::factory()->create([
            'key' => 'home_intro',
            'is_enabled' => false,
        ]);

        $resolved = app(LeadSlotResolver::class)->resolve('home');

        $this->assertNull($resolved['home_intro']);
    }

    public function test_returns_null_if_no_assignment(): void
    {
        LeadSlot::factory()->create([
            'key' => 'home_intro',
            'is_enabled' => true,
        ]);

        $resolved = app(LeadSlotResolver::class)->resolve('home');

        $this->assertNull($resolved['home_intro']);
    }

    public function test_resolves_active_assignment_even_if_slot_and_box_types_differ(): void
    {
        $slot = LeadSlot::factory()->create([
            'key' => 'home_intro',
            'is_enabled' => true,
        ]);
        $leadBox = LeadBox::factory()->service()->active()->create();

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $leadBox->id,
        ]);

        $resolved = app(LeadSlotResolver::class)->resolve('home');

        $this->assertSame($leadBox->id, $resolved['home_intro']['leadBoxId']);
    }

    public function test_returns_null_if_inactive_lead_box(): void
    {
        $slot = LeadSlot::factory()->create([
            'key' => 'home_intro',
            'is_enabled' => true,
        ]);
        $leadBox = LeadBox::factory()->resource()->create([
            'status' => LeadBox::STATUS_INACTIVE,
        ]);

        LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $leadBox->id,
        ]);

        $resolved = app(LeadSlotResolver::class)->resolve('home');

        $this->assertNull($resolved['home_intro']);
    }

    public function test_homepage_fallback_slots_always_included(): void
    {
        config()->set('lead_blocks.page_slots.home', ['home_intro']);

        $resolved = app(LeadSlotResolver::class)->resolve('home');

        $this->assertArrayHasKey('home_intro', $resolved);
        $this->assertArrayHasKey('home_mid', $resolved);
        $this->assertArrayHasKey('home_bottom', $resolved);
    }

    public function test_presenter_is_called_correctly(): void
    {
        $slot = LeadSlot::factory()->create([
            'key' => 'home_intro',
            'is_enabled' => true,
        ]);
        $leadBox = LeadBox::factory()->resource()->active()->create();
        $assignment = LeadAssignment::factory()->create([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $leadBox->id,
        ]);

        $presenter = Mockery::mock(LeadBoxPresenter::class);
        $presenter->shouldReceive('present')
            ->once()
            ->withArgs(function ($passedLeadBox, $passedAssignment, $slotKey, $pageKey) use ($leadBox, $assignment): bool {
                return $passedLeadBox->is($leadBox)
                    && $passedAssignment->is($assignment)
                    && $slotKey === 'home_intro'
                    && $pageKey === 'home';
            })
            ->andReturn([
                'leadBoxId' => $leadBox->id,
                'type' => $leadBox->type,
                'title' => $leadBox->title,
                'shortText' => $leadBox->short_text,
                'buttonText' => $leadBox->button_text,
                'iconKey' => $leadBox->icon_key,
                'content' => $leadBox->content,
                'context' => [
                    'slotKey' => 'home_intro',
                    'pageKey' => 'home',
                ],
            ]);

        $resolver = new LeadSlotResolver($presenter);
        $resolved = $resolver->resolve('home');

        $this->assertSame($leadBox->id, $resolved['home_intro']['leadBoxId']);
    }
}
