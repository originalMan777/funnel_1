<?php

namespace Tests\Invariant;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VisibilityInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_public_eligible_posts_appear_in_public_blog_surfaces(): void
    {
        $category = Category::factory()->create(['slug' => 'market-news']);
        $tag = Tag::factory()->create(['slug' => 'mortgage-tips']);

        $published = Post::factory()->published()->create([
            'title' => 'Published Winner',
            'slug' => 'published-winner',
            'category_id' => $category->id,
        ]);
        $published->tags()->sync([$tag->id]);

        Post::factory()->create([
            'title' => 'Draft Loser',
            'slug' => 'draft-loser',
            'category_id' => $category->id,
        ]);

        Post::factory()->scheduled()->create([
            'title' => 'Scheduled Loser',
            'slug' => 'scheduled-loser',
            'category_id' => $category->id,
        ]);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Index')
                ->where('posts.data.0.slug', 'published-winner')
            );

        $this->get(route('blog.category', $category->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Category')
                ->where('posts.data.0.slug', 'published-winner')
                ->has('posts.data', 1)
            );

        $this->get(route('blog.tag', $tag->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Tag')
                ->where('posts.data.0.slug', 'published-winner')
                ->has('posts.data', 1)
            );
    }

    public function test_draft_and_future_posts_never_leak_by_direct_slug(): void
    {
        $draft = Post::factory()->create(['slug' => 'draft-hidden']);
        $scheduled = Post::factory()->scheduled()->create(['slug' => 'scheduled-hidden']);

        $this->get(route('blog.show', $draft->slug))->assertNotFound();
        $this->get(route('blog.show', $scheduled->slug))->assertNotFound();
    }
}
