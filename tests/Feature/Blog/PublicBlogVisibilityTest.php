<?php

namespace Tests\Feature\Blog;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicBlogVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_only_lists_published_posts(): void
    {
        $published = Post::factory()->published()->create(['title' => 'Published Post']);
        Post::factory()->create(['title' => 'Draft Post']);
        Post::factory()->scheduled()->create(['title' => 'Scheduled Post']);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Index')
                ->where('posts.data.0.title', $published->title)
                ->missing('posts.data.1')
            );
    }

    public function test_draft_posts_are_not_publicly_visible(): void
    {
        $post = Post::factory()->create();

        $this->get(route('blog.show', $post->slug))->assertNotFound();
    }

    public function test_future_dated_posts_are_not_publicly_visible(): void
    {
        $post = Post::factory()->scheduled()->create();

        $this->get(route('blog.show', $post->slug))->assertNotFound();
    }

    public function test_archived_posts_are_not_publicly_visible(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Archived Post',
            'slug' => 'archived-post',
            'archived_at' => now(),
        ]);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertDontSee('Archived Post');

        $this->get(route('blog.show', $post->slug))->assertNotFound();
    }

    public function test_published_posts_are_visible_on_show_category_and_tag_pages(): void
    {
        $category = Category::factory()->create(['name' => 'Market News', 'slug' => 'market-news']);
        $tag = Tag::factory()->create(['name' => 'Mortgage Tips', 'slug' => 'mortgage-tips']);
        $post = Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Visible Post',
            'slug' => 'visible-post',
        ]);
        $post->tags()->sync([$tag->id]);

        $this->get(route('blog.show', $post->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Show')
                ->where('post.title', 'Visible Post')
                ->where('post.category.slug', 'market-news')
            );

        $this->get(route('blog.category', $category->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Category')
                ->where('posts.data.0.slug', 'visible-post')
            );

        $this->get(route('blog.tag', $tag->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Tag')
                ->where('posts.data.0.slug', 'visible-post')
            );
    }

    public function test_blog_index_category_counts_only_include_published_posts(): void
    {
        $category = Category::factory()->create(['name' => 'Tips', 'slug' => 'tips']);
        Post::factory()->published()->count(2)->create(['category_id' => $category->id]);
        Post::factory()->count(2)->create(['category_id' => $category->id]);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Index')
                ->where('categories.0.slug', 'tips')
                ->where('categories.0.count', 2)
            );
    }
}
