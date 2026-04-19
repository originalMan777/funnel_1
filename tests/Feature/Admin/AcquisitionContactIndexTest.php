<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\AcquisitionContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AcquisitionContactIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_acquisition_contact_index(): void
    {
        $this->get(route('admin.acquisition.contacts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_users_are_forbidden_from_acquisition_contact_index(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.acquisition.contacts.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_paginated_acquisition_contacts_sorted_by_latest_activity_then_created_at(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $version = app(HandleInertiaRequests::class)->version(request());

        $oldest = AcquisitionContact::query()->forceCreate([
            'display_name' => 'Oldest Contact',
            'primary_email' => 'oldest@example.com',
            'primary_phone' => '8685550001',
            'state' => 'new',
            'source_type' => 'lead_submission',
            'source_label' => 'Contact Form',
            'created_at' => Carbon::parse('2026-04-10 09:00:00'),
            'updated_at' => Carbon::parse('2026-04-10 09:00:00'),
        ]);

        $fallbackNewest = AcquisitionContact::query()->forceCreate([
            'display_name' => 'Fallback Newest',
            'primary_email' => 'fallback@example.com',
            'primary_phone' => '8685550002',
            'state' => 'working',
            'source_type' => 'popup_submission',
            'source_label' => 'Homepage Popup',
            'created_at' => Carbon::parse('2026-04-12 12:00:00'),
            'updated_at' => Carbon::parse('2026-04-12 12:00:00'),
        ]);

        $activeNewest = AcquisitionContact::query()->forceCreate([
            'display_name' => 'Active Newest',
            'primary_email' => 'active@example.com',
            'primary_phone' => '8685550003',
            'state' => 'qualified',
            'source_type' => 'lead_submission',
            'source_label' => 'Consultation Request',
            'last_activity_at' => Carbon::parse('2026-04-13 08:30:00'),
            'created_at' => Carbon::parse('2026-04-11 11:00:00'),
            'updated_at' => Carbon::parse('2026-04-13 08:30:00'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.acquisition.contacts.index'), [
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Inertia-Version' => $version,
            ])
            ->assertOk()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Admin/Acquisition/Contacts/Index')
            ->assertJsonPath('props.contacts.data.0.id', $activeNewest->id)
            ->assertJsonPath('props.contacts.data.0.display_name', 'Active Newest')
            ->assertJsonPath('props.contacts.data.0.email', 'active@example.com')
            ->assertJsonPath('props.contacts.data.1.id', $fallbackNewest->id)
            ->assertJsonPath('props.contacts.data.1.display_name', 'Fallback Newest')
            ->assertJsonPath('props.contacts.data.2.id', $oldest->id)
            ->assertJsonPath('props.contacts.data.2.display_name', 'Oldest Contact')
            ->assertJsonPath('props.contacts.data.0.state', 'qualified')
            ->assertJsonPath('props.contacts.data.0.source_type', 'lead_submission')
            ->assertJsonPath('props.contacts.data.0.source_label', 'Consultation Request')
            ->assertJsonPath('props.contacts.per_page', 25)
            ->assertJsonCount(3, 'props.contacts.data');
    }
}
