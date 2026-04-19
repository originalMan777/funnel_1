<?php

namespace Tests\Feature\Public\LeadSlots;

use App\Models\BlogIndexSection;
use App\Models\Category;
use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicLeadSlotPayloadConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_blog_index_and_blog_show_use_the_same_lead_slot_render_model_contract(): void
    {
        BlogIndexSection::query()->update(['enabled' => false]);

        $this->assignSlot(
            'home_mid',
            LeadBox::factory()->service()->active()->create([
                'title' => 'Home Service Title',
                'short_text' => 'Home Service Short',
                'button_text' => 'Home CTA',
            ]),
            [
                'override_title' => 'Home Override Title',
                'override_short_text' => 'Home Override Short',
                'override_button_text' => 'Home Override CTA',
            ],
        );

        $this->assignSlot(
            'blog_index_mid_lead',
            LeadBox::factory()->offer()->active()->create([
                'title' => 'Blog Index Offer',
                'short_text' => 'Index Short',
                'button_text' => 'Index CTA',
            ]),
            [
                'override_title' => 'Index Override Title',
                'override_short_text' => 'Index Override Short',
                'override_button_text' => 'Index Override CTA',
            ],
        );

        $this->assignSlot(
            'blog_post_inline_1',
            LeadBox::factory()->offer()->active()->create([
                'title' => 'Blog Show Offer',
                'short_text' => 'Show Short',
                'button_text' => 'Show CTA',
            ]),
            [
                'override_title' => 'Show Override Title',
                'override_short_text' => 'Show Override Short',
                'override_button_text' => 'Show Override CTA',
            ],
        );

        $this->assignSlot(
            'blog_post_before_related',
            LeadBox::factory()->offer()->active()->create([
                'title' => 'Before Related Offer',
                'short_text' => 'Before Related Short',
                'button_text' => 'Before Related CTA',
            ]),
        );

        $category = Category::factory()->create(['name' => 'News', 'slug' => 'news']);
        $post = Post::factory()->published()->create([
            'title' => 'Lead Slot Blog Post',
            'slug' => 'lead-slot-blog-post',
            'category_id' => $category->id,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('leadSlots.home_mid.type', 'service')
                ->where('leadSlots.home_mid.title', 'Home Override Title')
                ->where('leadSlots.home_mid.shortText', 'Home Override Short')
                ->where('leadSlots.home_mid.buttonText', 'Home Override CTA')
                ->where('leadSlots.home_mid.context.slotKey', 'home_mid')
                ->where('leadSlots.home_mid.context.pageKey', 'home')
            );

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Index')
                ->where('leadSlots.blog_index_mid_lead.type', 'offer')
                ->where('leadSlots.blog_index_mid_lead.title', 'Index Override Title')
                ->where('leadSlots.blog_index_mid_lead.shortText', 'Index Override Short')
                ->where('leadSlots.blog_index_mid_lead.buttonText', 'Index Override CTA')
                ->where('leadSlots.blog_index_mid_lead.context.slotKey', 'blog_index_mid_lead')
                ->where('leadSlots.blog_index_mid_lead.context.pageKey', 'blog_index')
            );

        $this->get(route('blog.show', ['slug' => $post->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Show')
                ->where('leadSlots.blog_post_inline_1.type', 'offer')
                ->where('leadSlots.blog_post_inline_1.title', 'Show Override Title')
                ->where('leadSlots.blog_post_inline_1.shortText', 'Show Override Short')
                ->where('leadSlots.blog_post_inline_1.buttonText', 'Show Override CTA')
                ->where('leadSlots.blog_post_inline_1.context.slotKey', 'blog_post_inline_1')
                ->where('leadSlots.blog_post_inline_1.context.pageKey', 'blog_show')
                ->where('leadSlots.blog_post_before_related.type', 'offer')
                ->where('leadSlots.blog_post_before_related.context.pageKey', 'blog_show')
            );
    }

    private function assignSlot(string $slotKey, LeadBox $leadBox, array $overrides = []): void
    {
        $slot = LeadSlot::factory()->create([
            'key' => $slotKey,
            'is_enabled' => true,
        ]);

        LeadAssignment::factory()->create(array_merge([
            'lead_slot_id' => $slot->id,
            'lead_box_id' => $leadBox->id,
        ], $overrides));
    }
}
