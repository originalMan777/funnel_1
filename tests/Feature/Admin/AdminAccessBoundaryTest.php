<?php

namespace Tests\Feature\Admin;

use App\Models\Campaign;
use App\Models\CampaignEnrollment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_admin_index_routes(): void
    {
        foreach ($this->adminIndexRoutes() as $route) {
            $this->get($route)->assertRedirect(route('login'));
        }
    }

    public function test_non_admin_users_are_forbidden_from_admin_index_routes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        foreach ($this->adminIndexRoutes() as $route) {
            $this->actingAs($user)->get($route)->assertForbidden();
        }
    }

    public function test_non_admin_users_are_forbidden_from_admin_write_routes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $leadSlot = LeadSlot::factory()->create(['key' => 'home_intro']);
        $campaign = Campaign::query()->create([
            'name' => 'Boundary Campaign',
            'status' => Campaign::STATUS_ACTIVE,
            'audience_type' => Campaign::AUDIENCE_LEADS,
            'entry_trigger' => 'lead.created',
        ]);
        $enrollment = CampaignEnrollment::query()->create([
            'campaign_id' => $campaign->id,
            'current_step_order' => 1,
            'status' => CampaignEnrollment::STATUS_ACTIVE,
            'next_run_at' => now()->addHour(),
            'started_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('admin.posts.store'), $this->validPostPayload())
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.media.store'), [
                'folder' => 'blog',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.popups.store'), $this->validPopupPayload())
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.lead-boxes.resource.store'), $this->validResourceLeadBoxPayload())
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.lead-slots.update', $leadSlot), [
                'is_enabled' => true,
                'lead_box_id' => null,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.campaign-enrollments.pause', $enrollment))
            ->assertForbidden();
    }

    private function adminIndexRoutes(): array
    {
        return [
            route('admin.index'),
            route('admin.analytics.index'),
            route('admin.analytics.metrics.index'),
            route('admin.analytics.funnels.index'),
            route('admin.analytics.scenarios.index'),
            route('admin.analytics.pages.index'),
            route('admin.analytics.ctas.index'),
            route('admin.analytics.lead-boxes.index'),
            route('admin.analytics.popups.index'),
            route('admin.analytics.clusters.show', ['clusterKey' => 'flow']),
            route('admin.analytics.clusters.show', ['clusterKey' => 'behavior']),
            route('admin.analytics.clusters.show', ['clusterKey' => 'results']),
            route('admin.analytics.clusters.show', ['clusterKey' => 'source']),
            route('admin.analytics.sub-clusters.show', ['clusterKey' => 'traffic', 'subClusterKey' => 'pages']),
            route('admin.analytics.sub-clusters.show', ['clusterKey' => 'capture', 'subClusterKey' => 'lead_boxes']),
            route('admin.analytics.metric-groups.show', ['clusterKey' => 'traffic', 'subClusterKey' => 'pages', 'metricGroupKey' => 'missing']),
            route('admin.analytics.metric-groups.show', ['clusterKey' => 'source', 'subClusterKey' => 'attribution', 'metricGroupKey' => 'first_touch']),
            route('admin.analytics.conversions.index'),
            route('admin.analytics.attribution.index'),
            route('admin.communications.index'),
            route('admin.communications.events.index'),
            route('admin.communications.deliveries.index'),
            route('admin.communications.syncs.index'),
            route('admin.communications.settings.index'),
            route('admin.campaigns.index'),
            route('admin.campaign-enrollments.index'),
            route('admin.posts.index'),
            route('admin.categories.index'),
            route('admin.tags.index'),
            route('admin.media.index'),
            route('admin.lead-boxes.index'),
            route('admin.lead-slots.index'),
            route('admin.popups.index'),
            route('admin.content-formula.index'),
        ];
    }

    private function validPostPayload(): array
    {
        return [
            'title' => 'Access Test Post',
            'content' => '<p>Body</p>',
            'sources' => 'https://example.com/source',
            'featured_image_path' => '/images/blog/test.png',
        ];
    }

    private function validPopupPayload(): array
    {
        return [
            'name' => 'Access Popup',
            'type' => 'general',
            'role' => 'standard',
            'priority' => 10,
            'headline' => 'Access popup',
            'cta_text' => 'Submit',
            'layout' => 'centered',
            'trigger_type' => 'time',
            'device' => 'all',
            'frequency' => 'once_day',
            'audience' => 'guests',
            'suppression_scope' => 'all_lead_popups',
            'lead_type' => 'general',
            'post_submit_action' => 'message',
        ];
    }

    private function validResourceLeadBoxPayload(): array
    {
        return [
            'status' => LeadBox::STATUS_ACTIVE,
            'internal_name' => 'Access test resource box',
            'title' => 'Resource box',
            'short_text' => 'Helpful text.',
            'button_text' => 'Get it',
            'icon_key' => 'book-open',
            'visual_preset' => 'default',
        ];
    }
}
