<?php

namespace Tests\Integration\Acquisition;

use App\Models\AcquisitionContact;
use App\Models\Lead;
use App\Services\Acquisition\AcquisitionContactResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcquisitionContactResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dedupes_by_normalized_email_before_phone(): void
    {
        $resolver = app(AcquisitionContactResolver::class);

        $first = $resolver->resolve([
            'name' => 'First Lead',
            'email' => 'Repeat@Example.com',
            'phone' => '868-555-1212',
            'contact_type' => 'inbound',
            'source_type' => 'lead_submission',
            'source_label' => 'contact',
        ]);

        $second = $resolver->resolve([
            'name' => 'Second Lead',
            'email' => 'repeat@example.com',
            'phone' => '868-555-9999',
            'contact_type' => 'inbound',
            'source_type' => 'popup_submission',
            'source_label' => 'popup',
        ]);

        $this->assertSame($first->id, $second->id);

        $contact = $first->fresh();

        $this->assertNotNull($contact);

        $this->assertSame('repeat@example.com', $contact->primary_email);
        $this->assertSame('repeat@example.com', $contact->normalized_email_key);
        $this->assertSame('8685551212', $contact->primary_phone);
        $this->assertSame('8685551212', $contact->normalized_phone_key);
        $this->assertSame('First Lead', $contact->display_name);
    }

    public function test_it_dedupes_by_normalized_phone_when_email_differs(): void
    {
        $resolver = app(AcquisitionContactResolver::class);

        $first = $resolver->resolve([
            'name' => 'Phone First',
            'email' => 'first@example.com',
            'phone' => '+1 (868) 555-1212 ext 89',
            'contact_type' => 'inbound',
            'source_type' => 'lead_submission',
            'source_label' => 'consultation',
        ]);

        $second = $resolver->resolve([
            'name' => 'Phone Second',
            'email' => 'second@example.com',
            'phone' => '868.555.1212',
            'contact_type' => 'inbound',
            'source_type' => 'popup_submission',
            'source_label' => 'popup',
        ]);

        $this->assertSame($first->id, $second->id);

        $contact = $first->fresh();

        $this->assertNotNull($contact);

        $this->assertSame('first@example.com', $contact->primary_email);
        $this->assertSame('8685551212', $contact->primary_phone);
        $this->assertSame('8685551212', $contact->normalized_phone_key);
    }

    public function test_public_intake_does_not_create_or_attach_a_company_from_source_url(): void
    {
        $lead = Lead::query()->create([
            'page_key' => 'contact',
            'source_url' => 'https://example.com/contact',
            'type' => 'contact',
            'first_name' => 'Lead Person',
            'email' => 'lead@example.com',
            'payload' => [
                'phone' => '868-555-1212',
            ],
        ]);

        $contact = app(AcquisitionContactResolver::class)->resolveFromLead($lead);

        $this->assertNull($contact->acquisition_company_id);
        $this->assertNull($contact->website_url_snapshot);
        $this->assertDatabaseCount('acquisition_companies', 0);
    }

    public function test_matched_contact_state_is_preserved(): void
    {
        $contact = AcquisitionContact::query()->create([
            'state' => 'qualified',
            'primary_email' => 'state@example.com',
            'display_name' => 'Existing Person',
        ]);

        $resolved = app(AcquisitionContactResolver::class)->resolve([
            'name' => 'Updated Name',
            'email' => 'STATE@example.com',
            'phone' => '868-555-1212',
            'contact_type' => 'inbound',
            'source_type' => 'lead_submission',
            'source_label' => 'contact',
        ]);

        $this->assertSame($contact->id, $resolved->id);

        $contact->refresh();

        $this->assertSame('qualified', $contact->state);
        $this->assertSame('state@example.com', $contact->normalized_email_key);
    }

    public function test_matched_contact_keeps_stronger_existing_fields(): void
    {
        $contact = AcquisitionContact::query()->create([
            'contact_type' => 'outbound',
            'state' => 'working',
            'source_type' => 'campaign_import',
            'source_label' => 'spring-list',
            'primary_email' => 'strong@example.com',
            'primary_phone' => '8685550000',
            'display_name' => 'Strong Name',
            'company_name_snapshot' => 'Strong Company',
            'city_snapshot' => 'Port of Spain',
            'state_snapshot' => 'Trinidad and Tobago',
        ]);

        $resolved = app(AcquisitionContactResolver::class)->resolve([
            'name' => 'Weaker Name',
            'email' => 'strong@example.com',
            'phone' => '868-555-1212',
            'company_name' => 'Incoming Company',
            'city' => 'San Fernando',
            'state' => 'Some State',
            'contact_type' => 'inbound',
            'source_type' => 'lead_submission',
            'source_label' => 'contact',
        ]);

        $this->assertSame($contact->id, $resolved->id);

        $contact->refresh();

        $this->assertSame('hybrid', $contact->contact_type);
        $this->assertSame('campaign_import', $contact->source_type);
        $this->assertSame('spring-list', $contact->source_label);
        $this->assertSame('strong@example.com', $contact->primary_email);
        $this->assertSame('8685550000', $contact->primary_phone);
        $this->assertSame('Strong Name', $contact->display_name);
        $this->assertSame('Strong Company', $contact->company_name_snapshot);
        $this->assertSame('Port of Spain', $contact->city_snapshot);
        $this->assertSame('Trinidad and Tobago', $contact->state_snapshot);
    }

    public function test_it_matches_deterministically_when_duplicate_contacts_already_exist(): void
    {
        $first = AcquisitionContact::query()->create([
            'primary_email' => 'duplicate@example.com',
            'display_name' => 'First Duplicate',
        ]);

        $second = AcquisitionContact::query()->create([
            'primary_email' => 'duplicate@example.com',
            'display_name' => 'Second Duplicate',
        ]);

        $resolved = app(AcquisitionContactResolver::class)->resolve([
            'name' => 'Incoming Duplicate',
            'email' => 'duplicate@example.com',
            'contact_type' => 'inbound',
            'source_type' => 'lead_submission',
            'source_label' => 'contact',
        ]);

        $this->assertSame($first->id, $resolved->id);

        $first->refresh();
        $second->refresh();

        $this->assertSame('duplicate@example.com', $first->normalized_email_key);
        $this->assertNull($second->normalized_email_key);
        $this->assertSame(2, AcquisitionContact::query()->count());
    }
}
