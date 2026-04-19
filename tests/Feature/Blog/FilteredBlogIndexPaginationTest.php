<?php

namespace Tests\Feature\Blog;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FilteredBlogIndexPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_filter_prioritizes_matching_posts_before_fallback_posts(): void
    {
        $focusCategory = Category::factory()->create(['name' => 'Focus', 'slug' => 'focus']);
        $otherCategory = Category::factory()->create(['name' => 'Other', 'slug' => 'other']);

        $matchingNewest = $this->createPublishedPost('Category Match Newest', now()->subMinute(), $focusCategory);
        $matchingOlder = $this->createPublishedPost('Category Match Older', now()->subMinutes(3), $focusCategory);
        $fallbackNewest = $this->createPublishedPost('Fallback Newest', now()->subSeconds(90), $otherCategory);
        $fallbackOlder = $this->createPublishedPost('Fallback Older', now()->subMinutes(5), $otherCategory);

        $this->get(route('blog.index', ['category' => $focusCategory->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Index')
                ->where('posts.data.0.title', $matchingNewest->title)
                ->where('posts.data.1.title', $matchingOlder->title)
                ->where('posts.data.2.title', $fallbackNewest->title)
                ->where('posts.data.3.title', $fallbackOlder->title)
            );
    }

    public function test_tag_filter_prioritizes_matching_posts_before_fallback_posts(): void
    {
        $category = Category::factory()->create(['name' => 'General', 'slug' => 'general']);
        $focusTag = Tag::factory()->create(['name' => 'Focus Tag', 'slug' => 'focus-tag']);

        $matchingNewest = $this->createPublishedPost('Tag Match Newest', now()->subMinute(), $category, [$focusTag]);
        $matchingOlder = $this->createPublishedPost('Tag Match Older', now()->subMinutes(3), $category, [$focusTag]);
        $fallbackNewest = $this->createPublishedPost('Tag Fallback Newest', now()->subSeconds(90), $category);
        $fallbackOlder = $this->createPublishedPost('Tag Fallback Older', now()->subMinutes(5), $category);

        $this->get(route('blog.index', ['tag' => $focusTag->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Index')
                ->where('posts.data.0.title', $matchingNewest->title)
                ->where('posts.data.1.title', $matchingOlder->title)
                ->where('posts.data.2.title', $fallbackNewest->title)
                ->where('posts.data.3.title', $fallbackOlder->title)
            );
    }

    public function test_category_and_tag_filters_prioritize_any_matching_post_once_before_fallback_posts(): void
    {
        $focusCategory = Category::factory()->create(['name' => 'Focus', 'slug' => 'focus']);
        $otherCategory = Category::factory()->create(['name' => 'Other', 'slug' => 'other']);
        $focusTag = Tag::factory()->create(['name' => 'Focus Tag', 'slug' => 'focus-tag']);

        $categoryMatch = $this->createPublishedPost('Category Match', now()->subMinute(), $focusCategory);
        $tagMatch = $this->createPublishedPost('Tag Match', now()->subMinutes(2), $otherCategory, [$focusTag]);
        $bothMatch = $this->createPublishedPost('Both Match', now()->subSeconds(30), $focusCategory, [$focusTag]);
        $fallback = $this->createPublishedPost('Fallback Post', now()->subMinutes(4), $otherCategory);

        $this->get(route('blog.index', [
            'category' => $focusCategory->slug,
            'tag' => $focusTag->slug,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Index')
                ->where('posts.data.0.title', $bothMatch->title)
                ->where('posts.data.1.title', $categoryMatch->title)
                ->where('posts.data.2.title', $tagMatch->title)
                ->where('posts.data.3.title', $fallback->title)
                ->where('posts.data', function ($posts): bool {
                    return collect($posts)->where('title', 'Both Match')->count() === 1;
                })
            );
    }

    public function test_filtered_index_keeps_stable_page_boundaries_across_pages(): void
    {
        $focusCategory = Category::factory()->create(['name' => 'Focus', 'slug' => 'focus']);
        $otherCategory = Category::factory()->create(['name' => 'Other', 'slug' => 'other']);

        for ($index = 1; $index <= 10; $index++) {
            $this->createPublishedPost(
                'Priority ' . $index,
                now()->subMinutes($index),
                $focusCategory,
            );
        }

        for ($index = 1; $index <= 12; $index++) {
            $this->createPublishedPost(
                'Fallback ' . $index,
                now()->subMinutes(20 + $index),
                $otherCategory,
            );
        }

        $this->get(route('blog.index', ['category' => $focusCategory->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Index')
                ->has('posts.data', 18)
                ->where('posts.data.0.title', 'Priority 1')
                ->where('posts.data.9.title', 'Priority 10')
                ->where('posts.data.10.title', 'Fallback 1')
                ->where('posts.data.17.title', 'Fallback 8')
            );

        $this->get(route('blog.index', ['category' => $focusCategory->slug, 'page' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Index')
                ->has('posts.data', 4)
                ->where('posts.data.0.title', 'Fallback 9')
                ->where('posts.data.3.title', 'Fallback 12')
            );
    }

    private function createPublishedPost(string $title, $publishedAt, Category $category, array $tags = []): Post
    {
        $post = Post::factory()->published()->create([
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
            'category_id' => $category->id,
            'published_at' => $publishedAt,
        ]);

        if ($tags !== []) {
            $post->tags()->sync(collect($tags)->pluck('id')->all());
        }

        return $post;
    }
}
