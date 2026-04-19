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

class ContactFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_route_persists_a_public_lead(): void
    {
        $this->app->bind(TransactionalEmailProvider::class, FakeTransactionalEmailProvider::class);
        $this->app->bind(MarketingProvider::class, FakeMarketingProvider::class);
        config()->set('communications.admin_notification_email', 'ops@example.com');

        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'name' => 'Jameel Campo',
                'email' => 'jameel@example.com',
                'message' => 'Need help with a general question.',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHas('success');

        $lead = Lead::query()->latest('id')->firstOrFail();
        $contact = AcquisitionContact::query()->firstOrFail();

        $this->assertNull($lead->lead_box_id);
        $this->assertNull($lead->lead_slot_key);
        $this->assertSame('contact', $lead->page_key);
        $this->assertSame('contact', $lead->type);
        $this->assertSame('Jameel Campo', $lead->first_name);
        $this->assertSame('jameel@example.com', $lead->email);
        $this->assertSame('Need help with a general question.', $lead->payload['message']);
        $this->assertSame($contact->id, $lead->acquisition_contact_id);
        $this->assertSame('jameel@example.com', $contact->primary_email);
        $this->assertSame('Jameel Campo', $contact->display_name);

        $event = CommunicationEvent::query()->where('event_key', 'contact.requested')->firstOrFail();

        $this->assertSame($lead->id, $event->subject_id);
        $this->assertSame(CommunicationEvent::STATUS_PROCESSED, $event->status);
        $this->assertSame(5, CommunicationDelivery::query()->where('communication_event_id', $event->id)->count());
    }
}
