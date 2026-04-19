<?php

namespace Tests\Feature\Admin;

use App\Models\AcquisitionContact;
use App\Models\AcquisitionEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AcquisitionContactStateUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_contact_state_updates(): void
    {
        $contact = AcquisitionContact::query()->create([
            'state' => 'new',
        ]);

        $this->patch(route('admin.acquisition.contacts.update-state', $contact), [
            'state' => 'qualified',
        ])->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_update_contact_state(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $contact = AcquisitionContact::query()->create([
            'state' => 'new',
        ]);

        $this->actingAs($user)
            ->patch(route('admin.acquisition.contacts.update-state', $contact), [
                'state' => 'qualified',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_update_contact_state_and_log_event(): void
    {
        Carbon::setTestNow('2026-04-14 09:30:00');

        $admin = User::factory()->create(['is_admin' => true]);
        $contact = AcquisitionContact::query()->create([
            'state' => 'contacted',
            'last_activity_at' => Carbon::parse('2026-04-13 08:00:00'),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.acquisition.contacts.update-state', $contact), [
                'state' => 'qualified',
            ])
            ->assertRedirect(route('admin.acquisition.contacts.show', $contact));

        $contact->refresh();

        $this->assertSame('qualified', $contact->state);
        $this->assertSame('2026-04-14 09:30:00', $contact->last_activity_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-14 09:30:00', $contact->qualified_at?->format('Y-m-d H:i:s'));
        $this->assertNull($contact->converted_at);

        $event = AcquisitionEvent::query()->latest('id')->first();

        $this->assertNotNull($event);
        $this->assertSame($contact->id, $event->acquisition_contact_id);
        $this->assertSame('state_changed', $event->event_type);
        $this->assertSame('user', $event->actor_type);
        $this->assertSame($admin->id, $event->actor_user_id);
        $this->assertSame('State changed', $event->summary);
        $this->assertSame([
            'from_state' => 'contacted',
            'to_state' => 'qualified',
        ], $event->details);
        $this->assertSame('2026-04-14 09:30:00', $event->occurred_at?->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_converted_state_preserves_existing_qualified_timestamp_and_sets_converted_timestamp_once(): void
    {
        Carbon::setTestNow('2026-04-14 10:00:00');

        $admin = User::factory()->create(['is_admin' => true]);
        $contact = AcquisitionContact::query()->forceCreate([
            'state' => 'qualified',
            'qualified_at' => Carbon::parse('2026-04-12 15:45:00'),
            'created_at' => Carbon::parse('2026-04-12 15:00:00'),
            'updated_at' => Carbon::parse('2026-04-12 15:45:00'),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.acquisition.contacts.update-state', $contact), [
                'state' => 'converted',
            ])
            ->assertRedirect(route('admin.acquisition.contacts.show', $contact));

        $contact->refresh();

        $this->assertSame('2026-04-12 15:45:00', $contact->qualified_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-14 10:00:00', $contact->converted_at?->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_invalid_state_is_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $contact = AcquisitionContact::query()->create([
            'state' => 'new',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.acquisition.contacts.show', $contact))
            ->patch(route('admin.acquisition.contacts.update-state', $contact), [
                'state' => 'archived',
            ])
            ->assertRedirect(route('admin.acquisition.contacts.show', $contact))
            ->assertSessionHasErrors(['state']);

        $contact->refresh();

        $this->assertSame('new', $contact->state);
        $this->assertDatabaseCount('acquisition_events', 0);
    }
}
