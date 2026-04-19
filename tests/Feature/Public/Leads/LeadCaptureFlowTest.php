<?php

namespace Tests\Feature\Public\Leads;

use App\Models\AcquisitionContact;
use App\Models\AcquisitionEvent;
use App\Models\AcquisitionSource;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadCaptureFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_submission_validation_failure_redirects_back_with_errors_and_persists_nothing(): void
    {
        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'message' => '',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHasErrors([
                'name',
                'email',
                'message',
            ]);

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_consultation_request_validation_failure_redirects_back_with_errors_and_persists_nothing(): void
    {
        $this->from(route('consultation.request'))
            ->post(route('consultation.request.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'phone' => '',
                'details' => '',
            ])
            ->assertRedirect(route('consultation.request'))
            ->assertSessionHasErrors([
                'name',
                'email',
                'phone',
                'details',
            ]);

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_successful_public_contact_and_consultation_submissions_store_plain_public_leads(): void
    {
        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'name' => 'Jameel Campo',
                'email' => 'contact@example.com',
                'message' => 'Need help with a general question.',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHas('success');

        $this->from(route('consultation.request'))
            ->post(route('consultation.request.store'), [
                'name' => 'Jameel Campo',
                'email' => 'consultation@example.com',
                'phone' => '555-1212',
                'details' => 'Need guidance on next steps before booking.',
            ])
            ->assertRedirect(route('consultation.request'))
            ->assertSessionHas('success');

        $contactLead = Lead::query()->where('type', 'contact')->firstOrFail();
        $consultationLead = Lead::query()->where('type', 'consultation')->firstOrFail();
        $contact = AcquisitionContact::query()
            ->where('primary_email', 'contact@example.com')
            ->firstOrFail();
        $consultationContact = AcquisitionContact::query()
            ->where('primary_email', 'consultation@example.com')
            ->firstOrFail();

        $this->assertNull($contactLead->lead_box_id);
        $this->assertNull($contactLead->lead_slot_key);
        $this->assertSame('contact', $contactLead->page_key);
        $this->assertSame($contact->id, $contactLead->acquisition_contact_id);

        $this->assertNull($consultationLead->lead_box_id);
        $this->assertNull($consultationLead->lead_slot_key);
        $this->assertSame('consultation_request', $consultationLead->page_key);
        $this->assertSame($consultationContact->id, $consultationLead->acquisition_contact_id);

        $this->assertDatabaseHas('acquisition_sources', [
            'acquisition_contact_id' => $contact->id,
            'source_type' => 'lead_submission',
            'source_table' => 'leads',
            'source_record_id' => $contactLead->id,
            'page_key' => 'contact',
        ]);

        $this->assertDatabaseHas('acquisition_sources', [
            'acquisition_contact_id' => $consultationContact->id,
            'source_type' => 'lead_submission',
            'source_table' => 'leads',
            'source_record_id' => $consultationLead->id,
            'page_key' => 'consultation_request',
        ]);

        $this->assertDatabaseHas('acquisition_events', [
            'acquisition_contact_id' => $contact->id,
            'event_type' => 'lead_submission',
            'related_table' => 'leads',
            'related_id' => $contactLead->id,
        ]);

        $this->assertDatabaseHas('acquisition_events', [
            'acquisition_contact_id' => $consultationContact->id,
            'event_type' => 'lead_submission',
            'related_table' => 'leads',
            'related_id' => $consultationLead->id,
        ]);

        $this->assertSame(2, AcquisitionSource::query()->count());
        $this->assertSame(2, AcquisitionEvent::query()->count());
    }
}
