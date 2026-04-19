<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Services\LeadSlots\LeadSlotResolver;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly LeadSlotResolver $leadSlotResolver,
    ) {
    }

    public function __invoke(): Response
    {
        return Inertia::render('Home', [
            'featuredSliderCategories' => $this->buildFeaturedSliderCategories(),
            'leadSlots' => $this->leadSlotResolver->resolve('home'),
        ]);
    }

    /**
     * Build slider categories:
     * - only categories with 2+ published posts
     * - max 8 categories
     * - each includes top 2 articles
     */
    private function buildFeaturedSliderCategories(): array
    {
        $categories = Category::query()
            ->select(['id', 'name', 'slug'])
            ->whereHas('posts', function ($query) {
                $query->published();
            }, '>=', 2)
            ->withCount(['posts as published_posts_count' => function ($query) {
                $query->published();
            }])
            ->orderByDesc('published_posts_count')
            ->limit(8)
            ->get();

        $postsByCategory = Post::query()
            ->published()
            ->whereIn('category_id', $categories->pluck('id'))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get([
                'id',
                'title',
                'slug',
                'excerpt',
                'featured_image_path',
                'published_at',
                'category_id',
            ])
            ->groupBy('category_id')
            ->map(fn (Collection $posts) => $posts->take(2));

        return $categories
            ->map(function (Category $category) use ($postsByCategory) {
                $articles = $postsByCategory
                    ->get($category->id, collect())
                    ->map(fn (Post $post) => [
                        'id' => $post->id,
                        'title' => $post->title,
                        'href' => route('blog.show', ['slug' => $post->slug]),
                        'image_url' => $post->featured_image_url,
                        'excerpt' => $post->excerpt,
                    ])
                    ->values()
                    ->all();

                return [
                    'key' => $category->slug,
                    'title' => $category->name,
                    'href' => route('blog.index', ['category' => $category->slug]),
                    'articles' => $articles,
                ];
            })
            ->values()
            ->all();
    }
}
