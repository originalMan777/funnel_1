<?php

namespace Tests\Integration\Data;

use App\Models\Category;
use App\Models\LeadAssignment;
use App\Models\LeadBox;
use App\Models\LeadSlot;
use App\Models\Popup;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_factories_create_valid_baseline_records(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $post = Post::factory()->create();
        $resource = LeadBox::factory()->resource()->create();
        $service = LeadBox::factory()->service()->active()->create();
        $offer = LeadBox::factory()->offer()->active()->create();
        $slot = LeadSlot::factory()->create();
        $assignment = LeadAssignment::factory()->create();
        $popup = Popup::factory()->create();

        $this->assertNotNull($user->id);
        $this->assertNotNull($category->id);
        $this->assertNotNull($tag->id);
        $this->assertSame(Post::STATUS_DRAFT, $post->status);
        $this->assertSame(LeadBox::TYPE_RESOURCE, $resource->type);
        $this->assertSame(LeadBox::TYPE_SERVICE, $service->type);
        $this->assertSame(LeadBox::TYPE_OFFER, $offer->type);
        $this->assertNotNull($slot->id);
        $this->assertNotNull($assignment->id);
        $this->assertTrue($popup->is_active);
    }

    public function test_post_factory_states_remain_semantically_valid(): void
    {
        $published = Post::factory()->published()->create();
        $scheduled = Post::factory()->scheduled()->create();

        $this->assertSame(Post::STATUS_PUBLISHED, $published->status);
        $this->assertTrue($published->published_at->isPast());

        $this->assertSame(Post::STATUS_PUBLISHED, $scheduled->status);
        $this->assertTrue($scheduled->published_at->isFuture());
    }
}
