<?php

namespace Tests\Feature\Public\Leads;

use App\Models\AcquisitionContact;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\Lead;
use App\Services\Communications\Contracts\MarketingProvider;
use App\Services\Communications\Contracts\TransactionalEmailProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\Communications\FakeMarketingProvider;
use Tests\Fakes\Communications\FakeTransactionalEmailProvider;
use Tests\TestCase;

class ConsultationRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_consultation_request_route_persists_a_public_lead(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, FakeMarketingProvider::class);
        config()->set('communications.admin_notification_email', 'ops@example.com');

        $this->from(route('consultation.request'))
            ->post(route('consultation.request.store'), [
                'name' => 'Jameel Campo',
                'email' => 'jameel@example.com',
                'phone' => '555-1212',
                'details' => 'Need guidance on next steps before booking.',
            ])
            ->assertRedirect(route('consultation.request'))
            ->assertSessionHas('success');

        $lead = Lead::query()->latest('id')->firstOrFail();
        $contact = AcquisitionContact::query()->firstOrFail();

        $this->assertNull($lead->lead_box_id);
        $this->assertNull($lead->lead_slot_key);
        $this->assertSame('consultation_request', $lead->page_key);
        $this->assertSame('consultation', $lead->type);
        $this->assertSame('Jameel Campo', $lead->first_name);
        $this->assertSame('jameel@example.com', $lead->email);
        $this->assertSame('555-1212', $lead->payload['phone']);
        $this->assertSame('Need guidance on next steps before booking.', $lead->payload['details']);
        $this->assertSame($contact->id, $lead->acquisition_contact_id);
        $this->assertSame('jameel@example.com', $contact->primary_email);
        $this->assertSame('5551212', $contact->primary_phone);

        $event = CommunicationEvent::query()->where('event_key', 'lead.consultation_requested')->firstOrFail();

        $this->assertSame($lead->id, $event->subject_id);
        $this->assertSame(CommunicationEvent::STATUS_PROCESSED, $event->status);
        $this->assertSame(6, CommunicationDelivery::query()->where('communication_event_id', $event->id)->count());
    }
}
