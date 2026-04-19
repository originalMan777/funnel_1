<?php

namespace Tests\Feature\Admin;

use App\Models\Campaign;
use App\Models\CommunicationTemplate;
use App\Models\CommunicationTemplateVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CampaignAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_view_campaign_pages_create_a_campaign_and_update_it(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $template = CommunicationTemplate::query()->create([
            'key' => 'campaign.follow_up',
            'name' => 'Campaign Follow Up',
            'channel' => CommunicationTemplate::CHANNEL_EMAIL,
            'category' => CommunicationTemplate::CATEGORY_TRANSACTIONAL,
            'status' => CommunicationTemplate::STATUS_ACTIVE,
        ]);

        $version = CommunicationTemplateVersion::query()->create([
            'communication_template_id' => $template->id,
            'version_number' => 1,
            'subject' => 'Hello',
            'html_body' => '<p>Hello</p>',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $template->forceFill([
            'current_version_id' => $version->id,
        ])->save();

        $this->actingAs($admin)
            ->get(route('admin.campaigns.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Campaigns/Index'));

        $this->actingAs($admin)
            ->get(route('admin.campaigns.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Campaigns/Create')
                ->has('formOptions.statusOptions')
                ->has('formOptions.entryTriggerOptions')
                ->has('formOptions.templateOptions')
            );

        $this->actingAs($admin)
            ->post(route('admin.campaigns.store'), [
                'name' => 'Welcome Campaign',
                'status' => Campaign::STATUS_ACTIVE,
                'audience_type' => Campaign::AUDIENCE_LEADS,
                'entry_trigger' => 'lead.created',
                'description' => 'Initial nurture sequence.',
                'steps' => [
                    [
                        'step_order' => 1,
                        'delay_amount' => 0,
                        'delay_unit' => 'days',
                        'send_mode' => 'template',
                        'template_id' => $template->id,
                        'is_enabled' => true,
                    ],
                    [
                        'step_order' => 2,
                        'delay_amount' => 3,
                        'delay_unit' => 'days',
                        'send_mode' => 'custom',
                        'subject' => 'Custom follow-up',
                        'html_body' => '<p>Checking in.</p>',
                        'text_body' => '',
                        'is_enabled' => true,
                    ],
                ],
            ])
            ->assertRedirect();

        $campaign = Campaign::query()->firstOrFail();

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Welcome Campaign',
            'entry_trigger' => 'lead.created',
        ]);

        $this->assertDatabaseHas('campaign_steps', [
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'send_mode' => 'template',
            'template_id' => $template->id,
        ]);

        $this->assertDatabaseHas('campaign_steps', [
            'campaign_id' => $campaign->id,
            'step_order' => 2,
            'send_mode' => 'custom',
            'subject' => 'Custom follow-up',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.campaigns.edit', $campaign))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Campaigns/Edit')
                ->where('campaign.name', 'Welcome Campaign')
                ->has('campaign.steps', 2)
            );

        $this->actingAs($admin)
            ->put(route('admin.campaigns.update', $campaign), [
                'name' => 'Updated Campaign',
                'status' => Campaign::STATUS_PAUSED,
                'audience_type' => Campaign::AUDIENCE_POPUP_LEADS,
                'entry_trigger' => 'popup.submitted',
                'description' => 'Updated nurture sequence.',
                'steps' => [
                    [
                        'step_order' => 1,
                        'delay_amount' => 1,
                        'delay_unit' => 'hours',
                        'send_mode' => 'custom',
                        'subject' => 'Updated custom step',
                        'html_body' => '',
                        'text_body' => 'Updated text body',
                        'is_enabled' => true,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.campaigns.edit', $campaign));

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Campaign',
            'status' => Campaign::STATUS_PAUSED,
            'audience_type' => Campaign::AUDIENCE_POPUP_LEADS,
            'entry_trigger' => 'popup.submitted',
        ]);

        $this->assertDatabaseHas('campaign_steps', [
            'campaign_id' => $campaign->id,
            'step_order' => 1,
            'delay_unit' => 'hours',
            'send_mode' => 'custom',
            'subject' => 'Updated custom step',
        ]);
    }
}
