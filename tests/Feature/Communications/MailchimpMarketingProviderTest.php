<?php

namespace Tests\Feature\Communications;

use App\Models\AcquisitionContact;
use App\Services\Communications\CommunicationRuntimeConfig;
use App\Services\Communications\DTOs\MarketingAction;
use App\Services\Communications\DTOs\MarketingContact;
use App\Services\Communications\Providers\MailchimpMarketingProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MailchimpMarketingProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_mailchimp_provider_can_sync_contact_apply_tags_and_translate_trigger_mappings(): void
    {
        config()->set('services.mailchimp.api_key', 'key-us1');
        config()->set('services.mailchimp.server_prefix', 'us1');
        config()->set('communications.marketing.default_audience_key', 'audience.general');
        config()->set('communications.marketing.mailchimp.audiences', [
            'audience.general' => 'list_general',
        ]);
        config()->set('communications.marketing.mailchimp.tags', [
            'tag.contact.requested' => 'contact_requested',
        ]);
        config()->set('communications.marketing.mailchimp.triggers', [
            'trigger.contact.requested' => [
                'audience_key' => 'audience.general',
                'tags' => ['automation_contact_requested'],
            ],
        ]);

        Http::fake([
            'https://us1.api.mailchimp.com/3.0/lists/list_general/members/*' => Http::response([
                'id' => 'member-123',
            ], 200),
            'https://us1.api.mailchimp.com/3.0/lists/list_general/members/*/tags' => Http::response([], 204),
        ]);

        $contact = AcquisitionContact::query()->create([
            'contact_type' => 'inbound',
            'state' => 'new',
            'source_type' => 'lead_submission',
            'source_label' => 'contact',
            'primary_email' => 'contact@example.com',
            'normalized_email_key' => 'contact@example.com',
            'display_name' => 'Jameel Campo',
        ]);

        $marketingContact = new MarketingContact(
            acquisitionContactId: $contact->id,
            email: 'contact@example.com',
            name: 'Jameel Campo',
            phone: null,
        );

        $provider = new MailchimpMarketingProvider(app(CommunicationRuntimeConfig::class));

        $syncResult = $provider->syncContact(new MarketingAction(
            type: MarketingAction::TYPE_SYNC_CONTACT,
            actionKey: 'marketing.sync_contact',
            contact: $marketingContact,
            audienceKey: 'audience.general',
        ));

        $tagResult = $provider->applyTags(new MarketingAction(
            type: MarketingAction::TYPE_APPLY_TAGS,
            actionKey: 'marketing.tags.contact',
            contact: $marketingContact,
            audienceKey: 'audience.general',
            tagKeys: ['tag.contact.requested'],
        ));

        $triggerResult = $provider->triggerAutomation(new MarketingAction(
            type: MarketingAction::TYPE_TRIGGER_AUTOMATION,
            actionKey: 'marketing.trigger.contact.requested',
            contact: $marketingContact,
            triggerKey: 'trigger.contact.requested',
        ));

        $this->assertTrue($syncResult->successful);
        $this->assertSame('member-123', $syncResult->externalContactId);
        $this->assertTrue($tagResult->successful);
        $this->assertTrue($triggerResult->successful);

        Http::assertSentCount(3);
    }
}
