<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\AcquisitionContact;
use App\Models\AcquisitionEvent;
use App\Models\AcquisitionTouch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AcquisitionContactTouchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_when_logging_a_touch(): void
    {
        $contact = AcquisitionContact::query()->create([
            'state' => 'new',
        ]);

        $this->post(route('admin.acquisition.contacts.touches.store', $contact), [
            'type' => 'call',
            'status' => 'completed',
            'summary' => 'Called contact',
        ])->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_when_logging_a_touch(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $contact = AcquisitionContact::query()->create([
            'state' => 'new',
        ]);

        $this->actingAs($user)
            ->post(route('admin.acquisition.contacts.touches.store', $contact), [
                'type' => 'call',
                'status' => 'completed',
                'summary' => 'Called contact',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_log_a_completed_touch(): void
    {
        Carbon::setTestNow('2026-04-14 11:00:00');

        $admin = User::factory()->create(['is_admin' => true]);
        $contact = AcquisitionContact::query()->create([
            'state' => 'contacted',
            'primary_email' => 'person@example.com',
            'primary_phone' => '8685551111',
            'last_activity_at' => Carbon::parse('2026-04-13 09:00:00'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.acquisition.contacts.touches.store', $contact), [
                'type' => 'call',
                'status' => 'completed',
                'summary' => 'Called and left voicemail',
                'details' => 'Asked them to call back this afternoon.',
            ])
            ->assertRedirect(route('admin.acquisition.contacts.show', $contact));

        $contact->refresh();
        $touch = AcquisitionTouch::query()->latest('id')->first();
        $event = AcquisitionEvent::query()->latest('id')->first();

        $this->assertNotNull($touch);
        $this->assertSame($contact->id, $touch->acquisition_contact_id);
        $this->assertSame($admin->id, $touch->owner_user_id);
        $this->assertSame('call', $touch->touch_type);
        $this->assertSame('completed', $touch->status);
        $this->assertSame('Called and left voicemail', $touch->subject);
        $this->assertSame('Asked them to call back this afternoon.', $touch->body);
        $this->assertSame('person@example.com', $touch->recipient_email);
        $this->assertSame('8685551111', $touch->recipient_phone);
        $this->assertSame('2026-04-14 11:00:00', $touch->scheduled_for?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-14 11:00:00', $touch->completed_at?->format('Y-m-d H:i:s'));

        $this->assertSame('2026-04-14 11:00:00', $contact->last_activity_at?->format('Y-m-d H:i:s'));
        $this->assertNull($contact->next_action_at);

        $this->assertNotNull($event);
        $this->assertSame('touch_logged', $event->event_type);
        $this->assertSame('acquisition_touches', $event->related_table);
        $this->assertSame($touch->id, $event->related_id);
        $this->assertSame('Called and left voicemail', $event->summary);
        $this->assertSame([
            'touch_type' => 'call',
            'touch_status' => 'completed',
            'touch_details' => 'Asked them to call back this afternoon.',
        ], $event->details);

        Carbon::setTestNow();
    }

    public function test_admin_can_log_a_scheduled_follow_up_touch_and_update_next_action_conservatively(): void
    {
        Carbon::setTestNow('2026-04-14 12:00:00');

        $admin = User::factory()->create(['is_admin' => true]);
        $contact = AcquisitionContact::query()->forceCreate([
            'state' => 'engaged',
            'next_action_at' => Carbon::parse('2026-04-20 09:00:00'),
            'created_at' => Carbon::parse('2026-04-10 08:00:00'),
            'updated_at' => Carbon::parse('2026-04-12 08:00:00'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.acquisition.contacts.touches.store', $contact), [
                'type' => 'follow_up',
                'status' => 'scheduled',
                'summary' => 'Schedule Friday follow-up',
                'details' => 'Review financing status.',
                'scheduled_for' => '2026-04-18 10:30:00',
            ])
            ->assertRedirect(route('admin.acquisition.contacts.show', $contact));

        $contact->refresh();
        $touch = AcquisitionTouch::query()->latest('id')->first();
        $event = AcquisitionEvent::query()->latest('id')->first();

        $this->assertSame('scheduled', $touch?->status);
        $this->assertSame('follow_up', $touch?->touch_type);
        $this->assertSame('2026-04-18 10:30:00', $touch?->scheduled_for?->format('Y-m-d H:i:s'));
        $this->assertNull($touch?->completed_at);
        $this->assertSame('2026-04-14 12:00:00', $contact->last_activity_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-18 10:30:00', $contact->next_action_at?->format('Y-m-d H:i:s'));
        $this->assertSame('touch_logged', $event?->event_type);
        $this->assertSame('2026-04-18T10:30:00.000000Z', $event?->details['scheduled_for'] ?? null);

        Carbon::setTestNow();
    }

    public function test_scheduled_touch_does_not_replace_an_earlier_existing_next_action(): void
    {
        Carbon::setTestNow('2026-04-14 13:00:00');

        $admin = User::factory()->create(['is_admin' => true]);
        $contact = AcquisitionContact::query()->forceCreate([
            'state' => 'engaged',
            'next_action_at' => Carbon::parse('2026-04-15 09:00:00'),
            'created_at' => Carbon::parse('2026-04-10 08:00:00'),
            'updated_at' => Carbon::parse('2026-04-12 08:00:00'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.acquisition.contacts.touches.store', $contact), [
                'type' => 'follow_up',
                'status' => 'scheduled',
                'summary' => 'Schedule later follow-up',
                'scheduled_for' => '2026-04-18 16:00:00',
            ])
            ->assertRedirect(route('admin.acquisition.contacts.show', $contact));

        $contact->refresh();

        $this->assertSame('2026-04-15 09:00:00', $contact->next_action_at?->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_touch_appears_in_contact_timeline_via_logged_event(): void
    {
        Carbon::setTestNow('2026-04-14 14:00:00');

        $admin = User::factory()->create(['is_admin' => true]);
        $contact = AcquisitionContact::query()->create([
            'state' => 'contacted',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.acquisition.contacts.touches.store', $contact), [
                'type' => 'note',
                'status' => 'completed',
                'summary' => 'Documented buyer preferences',
                'details' => 'Prefers move-in ready homes.',
            ])
            ->assertRedirect(route('admin.acquisition.contacts.show', $contact));

        $version = app(HandleInertiaRequests::class)->version(request());

        $this->actingAs($admin)
            ->get(route('admin.acquisition.contacts.show', $contact), [
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Inertia-Version' => $version,
            ])
            ->assertOk()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Admin/Acquisition/Contacts/Show')
            ->assertJsonFragment([
                'type' => 'touch_logged',
                'title' => 'Documented buyer preferences',
                'subtitle' => 'Note • Completed',
            ])
            ->assertJsonFragment([
                'touch_type' => 'note',
                'touch_status' => 'completed',
                'touch_details' => 'Prefers move-in ready homes.',
            ]);

        Carbon::setTestNow();
    }
}
