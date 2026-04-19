<?php

namespace Tests\Feature\Home;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FeaturedSliderAssemblyTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_featured_slider_keeps_shape_and_uses_one_bulk_post_query(): void
    {
        $alpha = Category::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);
        $beta = Category::factory()->create(['name' => 'Beta', 'slug' => 'beta']);
        $excluded = Category::factory()->create(['name' => 'Excluded', 'slug' => 'excluded']);

        $alphaNewest = $this->createPublishedPost('Alpha Newest', 'alpha-newest', $alpha, now()->subMinute());
        $alphaOlder = $this->createPublishedPost('Alpha Older', 'alpha-older', $alpha, now()->subMinutes(2));
        $this->createPublishedPost('Alpha Oldest', 'alpha-oldest', $alpha, now()->subMinutes(3));

        $betaNewest = $this->createPublishedPost('Beta Newest', 'beta-newest', $beta, now()->subMinutes(4));
        $betaOlder = $this->createPublishedPost('Beta Older', 'beta-older', $beta, now()->subMinutes(5));
        $this->createPublishedPost('Beta Oldest', 'beta-oldest', $beta, now()->subMinutes(6));

        $this->createPublishedPost('Excluded Only One', 'excluded-only-one', $excluded, now()->subMinutes(7));

        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->has('featuredSliderCategories', 2)
                ->where('featuredSliderCategories.0.key', 'alpha')
                ->where('featuredSliderCategories.0.title', 'Alpha')
                ->where('featuredSliderCategories.0.href', route('blog.index', ['category' => 'alpha']))
                ->has('featuredSliderCategories.0.articles', 2)
                ->where('featuredSliderCategories.0.articles.0.id', $alphaNewest->id)
                ->where('featuredSliderCategories.0.articles.0.title', 'Alpha Newest')
                ->where('featuredSliderCategories.0.articles.0.href', route('blog.show', ['slug' => 'alpha-newest']))
                ->where('featuredSliderCategories.0.articles.1.id', $alphaOlder->id)
                ->where('featuredSliderCategories.1.key', 'beta')
                ->where('featuredSliderCategories.1.title', 'Beta')
                ->where('featuredSliderCategories.1.href', route('blog.index', ['category' => 'beta']))
                ->has('featuredSliderCategories.1.articles', 2)
                ->where('featuredSliderCategories.1.articles.0.id', $betaNewest->id)
                ->where('featuredSliderCategories.1.articles.1.id', $betaOlder->id)
            );

        $postSelectQueries = collect($queries)
            ->filter(fn (string $sql) => preg_match('/select .* from ["`]?posts["`]?.*category_id["`]? in.*order by ["`]?published_at["`]? desc/s', $sql) === 1);

        $this->assertCount(1, $postSelectQueries);
    }

    private function createPublishedPost(string $title, string $slug, Category $category, $publishedAt): Post
    {
        return Post::factory()->published()->create([
            'title' => $title,
            'slug' => $slug,
            'category_id' => $category->id,
            'published_at' => $publishedAt,
        ]);
    }
}
