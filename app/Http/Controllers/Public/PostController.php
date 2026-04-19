<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogIndexSection;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\LeadSlots\LeadSlotResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PostController extends Controller
{
    public function __construct(
        private readonly LeadSlotResolver $leadSlotResolver,
    ) {
    }

    public function index(Request $request)
    {
        $categories = Category::query()
            ->withCount([
                'posts' => fn ($query) => $query->published(),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category) => [
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->posts_count,
            ])
            ->values();

        $sections = BlogIndexSection::query()
            ->with('category:id,name,slug')
            ->whereIn('section_key', BlogIndexSection::SECTION_KEYS)
            ->get()
            ->keyBy('section_key');

        /*
         |--------------------------------------------------------------------------
         | Build configured sections first
         |--------------------------------------------------------------------------
         */

        $usedIds = [];

        $wideSection = $this->buildSectionPayload(
            $sections->get(BlogIndexSection::KEY_WIDE),
            6,
            $usedIds,
        );

        $clusterLeft = $this->buildSectionPayload(
            $sections->get(BlogIndexSection::KEY_CLUSTER_LEFT),
            4,
            $usedIds,
        );

        $clusterRight = $this->buildSectionPayload(
            $sections->get(BlogIndexSection::KEY_CLUSTER_RIGHT),
            4,
            $usedIds,
        );

        /*
         |--------------------------------------------------------------------------
         | Build main feed independently
         |--------------------------------------------------------------------------
         */

        $categorySlug = trim((string) $request->query('category', ''));
        $tagSlug = trim((string) $request->query('tag', ''));

        $focusCategory = $categorySlug !== ''
            ? Category::query()->where('slug', $categorySlug)->first(['id', 'name', 'slug'])
            : null;

        $focusTag = $tagSlug !== ''
            ? Tag::query()->where('slug', $tagSlug)->first(['id', 'name', 'slug'])
            : null;

        $baseSelect = [
            'id',
            'title',
            'slug',
            'excerpt',
            'content',
            'published_at',
            'category_id',
            'featured_image_path',
        ];

        $baseQuery = Post::query()
            ->published()
            ->with(['category:id,name,slug'])
            ->orderByDesc('published_at')
            ->select($baseSelect);

        if (! $focusCategory && ! $focusTag) {
            $posts = (clone $baseQuery)
                ->paginate(18)
                ->withQueryString();
        } else {
            $posts = Post::query()
                ->published()
                ->with(['category:id,name,slug'])
                ->select($baseSelect);

            $this->applyFilteredIndexOrdering($posts, $focusCategory, $focusTag);

            $posts = $posts
                ->paginate(18)
                ->withQueryString();
        }

        $posts->setCollection(
            $posts->getCollection()->map(fn (Post $post) => $this->mapPostCard($post))
        );

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
            'categories' => $categories,
            'wideSection' => $wideSection,
            'clusterSection' => [
                'left' => $clusterLeft,
                'right' => $clusterRight,
            ],
            'leadSlots' => $this->leadSlotResolver->resolve('blog_index'),
        ]);
    }

    private function applyFilteredIndexOrdering(Builder $query, ?Category $focusCategory, ?Tag $focusTag): void
    {
        $priorityClauses = [];
        $priorityBindings = [];

        if ($focusCategory) {
            $priorityClauses[] = 'posts.category_id = ?';
            $priorityBindings[] = $focusCategory->id;
        }

        if ($focusTag) {
            $priorityClauses[] = 'exists (
                select 1
                from post_tag
                where post_tag.post_id = posts.id
                  and post_tag.tag_id = ?
            )';
            $priorityBindings[] = $focusTag->id;
        }

        $query
            ->orderByRaw(
                'case when ' . implode(' or ', $priorityClauses) . ' then 0 else 1 end asc',
                $priorityBindings,
            )
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function show(string $slug)
    {
        $post = Post::query()
            ->published()
            ->where('slug', $slug)
            ->with([
                'category:id,name,slug',
                'tags:id,name,slug',
            ])
            ->firstOrFail();

        $postUrl = route('blog.show', ['slug' => $post->slug]);

        $plainContent = trim(preg_replace('/\s+/', ' ', strip_tags((string) $post->content)));

        $title = $post->meta_title ?: $post->title;

        $description = $post->meta_description
            ?: ($post->excerpt ?: Str::limit($plainContent, 160, ''));

        $ogTitle = $post->og_title ?: ($post->meta_title ?: $post->title);

        $ogDescription = $post->og_description
            ?: ($post->meta_description ?: ($post->excerpt ?: Str::limit($plainContent, 160, '')));

        $ogImage = $post->og_image_path ?: ($post->featured_image_url ?: null);

        $canonical = $post->canonical_url ?: $postUrl;

        $robots = $post->noindex ? 'noindex,nofollow' : 'index,follow';

        $relatedPosts = $this->buildRelatedPosts($post)
            ->map(fn (Post $relatedPost) => $this->mapPostCard($relatedPost))
            ->values();

        $previousPost = $this->findPreviousPost($post);
        $nextPost = $this->findNextPost($post);

        return Inertia::render('Blog/Show', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'published_at' => $post->published_at,
                'featured_image_url' => $post->featured_image_url,
                'content_html' => $post->content,
                'sources' => $post->sources,
                'category' => $post->category
                    ? [
                        'name' => $post->category->name,
                        'slug' => $post->category->slug,
                    ]
                    : null,
                'tags' => $post->tags->map(fn ($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ])->values(),
                'seo' => [
                    'url' => $postUrl,
                    'canonical_url' => $canonical,
                    'robots' => $robots,
                    'title' => $title,
                    'description' => $description,
                    'og' => [
                        'type' => 'article',
                        'title' => $ogTitle,
                        'description' => $ogDescription,
                        'image' => $ogImage,
                        'url' => $postUrl,
                    ],
                    'twitter' => [
                        'card' => 'summary_large_image',
                        'title' => $ogTitle,
                        'description' => $ogDescription,
                        'image' => $ogImage,
                    ],
                ],
            ],
            'leadSlots' => $this->leadSlotResolver->resolve('blog_show'),
            'relatedPosts' => $relatedPosts,
            'previousPost' => $previousPost
                ? [
                    'title' => $previousPost->title,
                    'slug' => $previousPost->slug,
                    'featured_image_url' => $previousPost->featured_image_url,
                ]
                : null,
            'nextPost' => $nextPost
                ? [
                    'title' => $nextPost->title,
                    'slug' => $nextPost->slug,
                    'featured_image_url' => $nextPost->featured_image_url,
                ]
                : null,
        ]);
    }

    public function category(Request $request, string $slug)
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->firstOrFail(['id', 'name', 'slug']);

        $posts = Post::query()
            ->published()
            ->where('category_id', $category->id)
            ->orderByDesc('published_at')
            ->select(['id', 'title', 'slug', 'excerpt', 'published_at', 'featured_image_path'])
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'published_at' => $post->published_at,
                'featured_image_url' => $post->featured_image_url,
            ]);

        return Inertia::render('Blog/Category', [
            'seo' => [
                'title' => 'Category: ' . $category->name,
                'description' => 'Articles filed under ' . $category->name . '.',
                'canonical_url' => route('blog.category', ['slug' => $category->slug]),
            ],
            'category' => [
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'posts' => $posts,
        ]);
    }

    public function tag(Request $request, string $slug)
    {
        $tag = Tag::query()
            ->where('slug', $slug)
            ->firstOrFail(['id', 'name', 'slug']);

        $posts = Post::query()
            ->published()
            ->whereHas('tags', fn ($q) => $q->whereKey($tag->id))
            ->orderByDesc('published_at')
            ->select(['id', 'title', 'slug', 'excerpt', 'published_at', 'featured_image_path'])
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'published_at' => $post->published_at,
                'featured_image_url' => $post->featured_image_url,
            ]);

        return Inertia::render('Blog/Tag', [
            'seo' => [
                'title' => 'Tag: ' . $tag->name,
                'description' => 'Articles tagged with ' . $tag->name . '.',
                'canonical_url' => route('blog.tag', ['slug' => $tag->slug]),
            ],
            'tag' => [
                'name' => $tag->name,
                'slug' => $tag->slug,
            ],
            'posts' => $posts,
        ]);
    }

    private function buildRelatedPosts(Post $post): Collection
    {
        $related = collect();
        $excludedIds = [$post->id];

        if ($post->category_id) {
            $categoryMatches = Post::query()
                ->published()
                ->with(['category:id,name,slug'])
                ->where('category_id', $post->category_id)
                ->whereNotIn('id', $excludedIds)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(3)
                ->get([
                    'id',
                    'title',
                    'slug',
                    'excerpt',
                    'content',
                    'published_at',
                    'category_id',
                    'featured_image_path',
                ]);

            $related = $related->concat($categoryMatches);
            $excludedIds = [...$excludedIds, ...$categoryMatches->pluck('id')->all()];
        }

        if ($related->count() < 3 && $post->tags->isNotEmpty()) {
            $tagIds = $post->tags->pluck('id');

            $tagMatches = Post::query()
                ->published()
                ->with(['category:id,name,slug'])
                ->whereNotIn('id', $excludedIds)
                ->whereHas('tags', fn ($query) => $query->whereIn('tags.id', $tagIds))
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(3 - $related->count())
                ->get([
                    'id',
                    'title',
                    'slug',
                    'excerpt',
                    'content',
                    'published_at',
                    'category_id',
                    'featured_image_path',
                ]);

            $related = $related->concat($tagMatches);
            $excludedIds = [...$excludedIds, ...$tagMatches->pluck('id')->all()];
        }

        if ($related->count() < 3) {
            $fallbackMatches = Post::query()
                ->published()
                ->with(['category:id,name,slug'])
                ->whereNotIn('id', $excludedIds)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(3 - $related->count())
                ->get([
                    'id',
                    'title',
                    'slug',
                    'excerpt',
                    'content',
                    'published_at',
                    'category_id',
                    'featured_image_path',
                ]);

            $related = $related->concat($fallbackMatches);
        }

        return $related->take(3)->values();
    }

    private function findPreviousPost(Post $post): ?Post
    {
        $previous = Post::query()
            ->published()
            ->where(function ($query) use ($post) {
                $query->where('published_at', '>', $post->published_at)
                    ->orWhere(function ($nested) use ($post) {
                        $nested->where('published_at', $post->published_at)
                            ->where('id', '>', $post->id);
                    });
            })
            ->orderBy('published_at')
            ->orderBy('id')
            ->first([
                'id',
                'title',
                'slug',
                'published_at',
                'featured_image_path',
            ]);

        if (! $previous) {
            $previous = Post::query()
                ->published()
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->first([
                    'id',
                    'title',
                    'slug',
                    'published_at',
                    'featured_image_path',
                ]);
        }

        return $previous;
    }

    private function findNextPost(Post $post): ?Post
    {
        $next = Post::query()
            ->published()
            ->where(function ($query) use ($post) {
                $query->where('published_at', '<', $post->published_at)
                    ->orWhere(function ($nested) use ($post) {
                        $nested->where('published_at', $post->published_at)
                            ->where('id', '<', $post->id);
                    });
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first([
                'id',
                'title',
                'slug',
                'published_at',
                'featured_image_path',
            ]);

        if (! $next) {
            $next = Post::query()
                ->published()
                ->orderBy('published_at')
                ->orderBy('id')
                ->first([
                    'id',
                    'title',
                    'slug',
                    'published_at',
                    'featured_image_path',
                ]);
        }

        return $next;
    }

    private function buildSectionPayload(?BlogIndexSection $section, int $limit, array &$usedIds): ?array
    {
        if (! $section || ! $section->enabled) {
            return null;
        }

        $query = Post::query()
            ->published()
            ->with(['category:id,name,slug'])
            ->orderByDesc('published_at')
            ->select([
                'id',
                'title',
                'slug',
                'excerpt',
                'content',
                'published_at',
                'category_id',
                'featured_image_path',
            ]);

        if (! empty($usedIds)) {
            $query->whereNotIn('id', $usedIds);
        }

        if ($section->source_type === BlogIndexSection::SOURCE_FEATURED) {
            $query->where('is_featured', true);
        } elseif ($section->source_type === BlogIndexSection::SOURCE_CATEGORY) {
            if (! $section->category_id) {
                return null;
            }

            $query->where('category_id', $section->category_id);
        }

        $posts = $query->limit($limit)->get();

        if ($posts->isEmpty()) {
            return null;
        }

        $usedIds = array_values(array_unique([
            ...$usedIds,
            ...$posts->pluck('id')->all(),
        ]));

        return [
            'title' => $this->resolveSectionTitle($section),
            'posts' => $posts->map(fn (Post $post) => $this->mapPostCard($post))->values()->all(),
        ];
    }

    private function mapPostCard(Post $post): array
    {
        $plainContent = trim(preg_replace('/\s+/', ' ', strip_tags((string) $post->content)));
        $cardSnippet = $post->excerpt ?: Str::limit($plainContent, 180, '…');

        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'card_snippet' => $cardSnippet ?: null,
            'published_at' => $post->published_at,
            'featured_image_url' => $post->featured_image_url,
            'category' => $post->category
                ? [
                    'name' => $post->category->name,
                    'slug' => $post->category->slug,
                ]
                : null,
        ];
    }

    private function resolveSectionTitle(BlogIndexSection $section): string
    {
        $override = trim((string) ($section->title_override ?? ''));

        if ($override !== '') {
            return $override;
        }

        return match ($section->source_type) {
            BlogIndexSection::SOURCE_FEATURED => 'Featured Articles',
            BlogIndexSection::SOURCE_CATEGORY => $section->category?->name
                ? 'From ' . $section->category->name
                : 'From Category',
            default => 'Latest Articles',
        };
    }
}
