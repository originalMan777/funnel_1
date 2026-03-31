<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_draft_post_with_new_taxonomy(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $existingTag = Tag::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.posts.store'), [
                'title' => 'New Draft Post',
                'content' => '<p>Draft body</p>',
                'sources' => 'https://example.com/source',
                'new_category' => 'Fresh Category',
                'tag_ids' => [$existingTag->id],
                'new_tags' => ['Fresh Tag'],
                'featured_image_path' => '/images/blog/post-image.png',
            ])
            ->assertRedirect();

        $post = Post::query()->where('title', 'New Draft Post')->firstOrFail();

        $this->assertSame(Post::STATUS_DRAFT, $post->status);
        $this->assertNull($post->published_at);
        $this->assertSame('/images/blog/post-image.png', $post->featured_image_path);
        $this->assertDatabaseHas('categories', ['name' => 'Fresh Category', 'id' => $post->category_id]);
        $this->assertDatabaseHas('tags', ['name' => 'Fresh Tag']);
        $this->assertCount(2, $post->tags);
    }

    public function test_admin_can_publish_and_unpublish_a_post(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.posts.publish', $post))
            ->assertRedirect(route('admin.posts.edit', $post));

        $post->refresh();
        $this->assertSame(Post::STATUS_PUBLISHED, $post->status);
        $this->assertNotNull($post->published_at);

        $this->actingAs($admin)
            ->post(route('admin.posts.unpublish', $post))
            ->assertRedirect(route('admin.posts.edit', $post));

        $post->refresh();
        $this->assertSame(Post::STATUS_DRAFT, $post->status);
        $this->assertNull($post->published_at);
    }

    public function test_admin_can_delete_a_post(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.posts.destroy', $post))
            ->assertRedirect(route('admin.posts.index'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_admin_can_archive_a_post_and_it_leaves_the_active_admin_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->published()->create([
            'title' => 'Archive Me',
            'slug' => 'archive-me',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.posts.archive', $post))
            ->assertSessionHas('success');

        $post->refresh();

        $this->assertNotNull($post->archived_at);

        $this->actingAs($admin)
            ->get(route('admin.posts.index'))
            ->assertOk()
            ->assertDontSee('Archive Me');

        $this->actingAs($admin)
            ->get(route('admin.posts.archived'))
            ->assertOk()
            ->assertSee('Archive Me');
    }

    public function test_post_store_rejects_invalid_featured_image_paths(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.posts.create'))
            ->post(route('admin.posts.store'), [
                'title' => 'Bad image path',
                'content' => '<p>Body</p>',
                'category_id' => $category->id,
                'featured_image_path' => 'https://evil.example.com/image.png',
            ])
            ->assertRedirect(route('admin.posts.create'))
            ->assertSessionHasErrors('featured_image_path');
    }

    public function test_post_store_normalizes_supported_media_path_shapes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.posts.store'), [
                'title' => 'Normalized media paths',
                'content' => '<p>Body</p>',
                'category_id' => $category->id,
                'featured_image_path' => 'storage/images/blog/cover.png',
                'og_image_path' => '/storage/images/blog/share.png',
            ])
            ->assertRedirect();

        $post = Post::query()->where('title', 'Normalized media paths')->firstOrFail();

        $this->assertSame('/images/blog/cover.png', $post->featured_image_path);
        $this->assertSame('/images/blog/share.png', $post->og_image_path);
    }
}
