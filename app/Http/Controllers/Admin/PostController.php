<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\Blog\PostContentSanitizer;
use App\Services\Media\SecureImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PostController extends Controller
{
    public function __construct(
        private readonly PostContentSanitizer $sanitizer,
        private readonly SecureImageUploadService $secureImageUploadService,
    ) {
    }

    private function requirePostAccess(Request $request): void
    {
        if (! $request->user()?->canManagePosts()) {
            abort(403);
        }
    }

    private function requirePublishAccess(Request $request): void
    {
        if (! $request->user()?->canPublishPosts()) {
            abort(403);
        }
    }

    private function requireDeleteAccess(Request $request): void
    {
        if (! $request->user()?->canDeletePosts()) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->requirePostAccess($request);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $allowedStatuses = ['all', Post::STATUS_DRAFT, Post::STATUS_PUBLISHED];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $posts = Post::query()
            ->whereNull('archived_at')
            ->with('category:id,name')
            ->select([
                'id',
                'title',
                'slug',
                'status',
                'published_at',
                'updated_at',
                'category_id',
                'featured_image_path',
            ])
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%' . $search . '%'))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status));

        $this->applyPostIndexOrdering($posts);

        $posts = $posts
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status,
                'category_name' => $post->category?->name,
                'published_at' => $post->published_at,
                'updated_at' => $post->updated_at,
                'featured_image_url' => $this->resolveMediaUrl($post->featured_image_path),
            ]);

        return Inertia::render('Admin/Posts/Index', [
            'posts' => $posts,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function archived(Request $request)
    {
        $this->requirePostAccess($request);

        $posts = Post::query()
            ->whereNotNull('archived_at')
            ->with('category:id,name')
            ->select([
                'id',
                'title',
                'slug',
                'status',
                'published_at',
                'archived_at',
                'updated_at',
                'category_id',
                'featured_image_path',
            ])
            ->orderByDesc('archived_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status,
                'category_name' => $post->category?->name,
                'published_at' => $post->published_at,
                'archived_at' => $post->archived_at,
                'updated_at' => $post->updated_at,
                'featured_image_url' => $this->resolveMediaUrl($post->featured_image_path),
            ]);

        return Inertia::render('Admin/Posts/Archived', [
            'posts' => $posts,
        ]);
    }

    public function create(Request $request)
    {
        $this->requirePostAccess($request);

        return Inertia::render('Admin/Posts/Create', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'navigator' => [
                'previous' => null,
                'next' => null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->requirePostAccess($request);

        $validated = $this->validatePost($request);
        $validated = $this->normalizeValidatedPostData($validated);
        $validated['content'] = $this->sanitizer->sanitizeForStorage((string) $validated['content']);

        $tagIds = Arr::pull($validated, 'tag_ids', []);
        $newTags = Arr::pull($validated, 'new_tags', []);
        $newCategory = Arr::pull($validated, 'new_category', null);

        if ($newCategory && trim($newCategory) !== '') {
            $categoryName = $this->cleanInlineText($newCategory);

            $category = Category::firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName]
            );

            $validated['category_id'] = $category->id;
        }

        if (is_array($newTags)) {
            foreach ($newTags as $tagName) {
                if (!is_string($tagName) || trim($tagName) === '') {
                    continue;
                }

                $safeTagName = $this->cleanInlineText($tagName);

                if ($safeTagName === '') {
                    continue;
                }

                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($safeTagName)],
                    ['name' => $safeTagName]
                );

                $tagIds[] = $tag->id;
            }
        }

        $tagIds = array_values(array_unique(array_map('intval', $tagIds)));

        /** @var UploadedFile|null $featuredImage */
        $featuredImage = Arr::pull($validated, 'featured_image');
        $selectedFeaturedImagePath = Arr::pull($validated, 'featured_image_path');
        Arr::pull($validated, 'remove_featured_image');

        $baseSlug = ($validated['slug'] ?? '') !== ''
            ? (string) $validated['slug']
            : (string) $validated['title'];

        $validated['slug'] = $this->generateUniqueSlug($baseSlug);

        if ($featuredImage) {
            $this->assertSafeUploadedImage($featuredImage);

            $validated['featured_image_path'] = $this->secureImageUploadService->storeForBlogPost(
                $featuredImage,
                $validated['slug']
            );
        } elseif ($selectedFeaturedImagePath) {
            $validated['featured_image_path'] = $selectedFeaturedImagePath;
        }

        $userId = (int) $request->user()->id;

        $post = Post::create([
            ...$validated,
            'status' => Post::STATUS_DRAFT,
            'published_at' => null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $post->tags()->sync($tagIds);

        $this->logAdminAction('post_created', $request, $post, [
            'status' => $post->status,
            'tag_count' => count($tagIds),
        ]);

        return redirect()->route('admin.posts.edit', $post);
    }

    public function show(Request $request, Post $post)
    {
        $this->requirePostAccess($request);

        $post->load(['category:id,name', 'tags:id,name']);

        return Inertia::render('Admin/Posts/Show', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'sources' => $post->sources,
                'category_name' => $post->category?->name,
                'tag_names' => $post->tags->pluck('name')->values()->all(),
                'featured_image_url' => $this->resolveMediaUrl($post->featured_image_path),
                'status' => $post->status,
                'published_at' => $post->published_at,
                'archived_at' => $post->archived_at,
                'updated_at' => $post->updated_at,
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'canonical_url' => $post->canonical_url,
                'og_title' => $post->og_title,
                'og_description' => $post->og_description,
                'og_image_path' => $post->og_image_path,
                'noindex' => (bool) $post->noindex,
            ],
            'navigator' => $this->buildPostNavigator($post),
        ]);
    }

    public function edit(Request $request, Post $post)
    {
        $this->requirePostAccess($request);

        $featuredImageUrl = $this->resolveMediaUrl($post->featured_image_path);

        return Inertia::render('Admin/Posts/Edit', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'sources' => $post->sources,
                'category_id' => $post->category_id,
                'tag_ids' => $post->tags()->pluck('tags.id')->all(),
                'is_featured' => (bool) $post->is_featured,
                'featured_image_path' => $post->featured_image_path,
                'featured_image_url' => $featuredImageUrl,
                'status' => $post->status,
                'published_at' => $post->published_at,
                'archived_at' => $post->archived_at,
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'canonical_url' => $post->canonical_url,
                'og_title' => $post->og_title,
                'og_description' => $post->og_description,
                'og_image_path' => $post->og_image_path,
                'noindex' => (bool) $post->noindex,
            ],
            'categories' => Category::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'navigator' => $this->buildPostNavigator($post),
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $this->requirePostAccess($request);

        $validated = $this->validatePost($request);
        $validated = $this->normalizeValidatedPostData($validated);
        $validated['content'] = $this->sanitizer->sanitizeForStorage((string) $validated['content']);

        $tagIds = Arr::pull($validated, 'tag_ids', []);
        $newTags = Arr::pull($validated, 'new_tags', []);
        $newCategory = Arr::pull($validated, 'new_category', null);

        if ($newCategory && trim($newCategory) !== '') {
            $categoryName = $this->cleanInlineText($newCategory);

            $category = Category::firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName]
            );

            $validated['category_id'] = $category->id;
        }

        if (is_array($newTags)) {
            foreach ($newTags as $tagName) {
                if (!is_string($tagName) || trim($tagName) === '') {
                    continue;
                }

                $safeTagName = $this->cleanInlineText($tagName);

                if ($safeTagName === '') {
                    continue;
                }

                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($safeTagName)],
                    ['name' => $safeTagName]
                );

                $tagIds[] = $tag->id;
            }
        }

        $tagIds = array_values(array_unique(array_map('intval', $tagIds)));

        /** @var UploadedFile|null $featuredImage */
        $featuredImage = Arr::pull($validated, 'featured_image');
        $selectedFeaturedImagePath = Arr::pull($validated, 'featured_image_path');
        $removeFeaturedImage = (bool) Arr::pull($validated, 'remove_featured_image', false);

        $baseSlug = ($validated['slug'] ?? '') !== ''
            ? (string) $validated['slug']
            : (string) $validated['title'];

        $validated['slug'] = $this->generateUniqueSlug($baseSlug, $post->id);

        if ($featuredImage) {
            $this->assertSafeUploadedImage($featuredImage);

            $validated['featured_image_path'] = $this->secureImageUploadService->storeForBlogPost(
                $featuredImage,
                $validated['slug']
            );
        } elseif ($selectedFeaturedImagePath) {
            $validated['featured_image_path'] = $selectedFeaturedImagePath;
        } elseif ($removeFeaturedImage) {
            $validated['featured_image_path'] = null;
        }

        $post->fill([
            ...$validated,
            'updated_by' => (int) $request->user()->id,
        ])->save();

        $post->tags()->sync($tagIds);

        $this->logAdminAction('post_updated', $request, $post, [
            'status' => $post->status,
            'tag_count' => count($tagIds),
        ]);

        return redirect()
            ->route('admin.posts.edit', $post)
            ->with('success', 'Post saved.');
    }

    public function publish(Request $request, Post $post)
    {
        $this->requirePublishAccess($request);

        $post->update([
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now(),
            'updated_by' => (int) $request->user()->id,
        ]);

        $this->logAdminAction('post_published', $request, $post, [
            'status' => $post->status,
        ]);

        return to_route('admin.posts.edit', $post);
    }

    public function unpublish(Request $request, Post $post)
    {
        $this->requirePublishAccess($request);

        $post->update([
            'status' => Post::STATUS_DRAFT,
            'published_at' => null,
            'updated_by' => (int) $request->user()->id,
        ]);

        $this->logAdminAction('post_unpublished', $request, $post, [
            'status' => $post->status,
        ]);

        return to_route('admin.posts.edit', $post);
    }

    public function archive(Request $request, Post $post)
    {
        $this->requirePostAccess($request);

        $post->update([
            'archived_at' => now(),
            'updated_by' => (int) $request->user()->id,
        ]);

        $this->logAdminAction('post_archived', $request, $post, [
            'archived_at' => $post->archived_at,
        ]);

        return redirect()
        ->route('admin.posts.archived')
        ->with(
            'success',
            'This post is archived. To access archives, use the Archives button in the left sidebar.'
        );
    }

    public function destroy(Request $request, Post $post)
    {
        $this->requireDeleteAccess($request);

        $postId = $post->id;
        $postTitle = $post->title;
        $postSlug = $post->slug;

        $post->tags()->detach();
        $post->delete();

        Log::channel(config('logging.default'))->info('admin_post_deleted', [
            'event' => 'admin_post_deleted',
            'user_id' => (int) $request->user()->id,
            'post_id' => $postId,
            'post_title' => $postTitle,
            'post_slug' => $postSlug,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'occurred_at' => now()->toIso8601String(),
        ]);

        return to_route('admin.posts.index')
            ->with('success', 'Post deleted successfully.');
    }

    private function validatePost(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:3000'],
            'content' => ['required', 'string', 'max:200000'],
            'sources' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'new_category' => ['nullable', 'string', 'max:255'],
            'new_tags' => ['nullable', 'array'],
            'new_tags.*' => ['string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:320'],
            'og_image_path' => [
                'nullable',
                'string',
                'max:2048',
                function ($attribute, $value, $fail) {
                    $trimmed = trim((string) $value);

                    if ($trimmed === '') {
                        return;
                    }

                    if (Str::startsWith($trimmed, ['http://', 'https://'])) {
                        return;
                    }

                    if (Post::normalizeManagedImagePath($trimmed) !== null) {
                        return;
                    }

                    if (Str::startsWith($trimmed, ['/storage/', 'storage/'])) {
                        return;
                    }

                    $fail('The OG image path must be a full URL or a supported local media path.');
                },
            ],
            'noindex' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'featured_image' => ['nullable', 'image', 'max:8192'],
            'featured_image_path' => [
                'nullable',
                'string',
                'max:2048',
                function ($attribute, $value, $fail) {
                    $trimmed = trim((string) $value);

                    if ($trimmed === '') {
                        return;
                    }

                    if (Post::normalizeManagedImagePath($trimmed) !== null) {
                        return;
                    }

                    if (!Str::startsWith($trimmed, ['/storage/', 'storage/'])) {
                        $fail('The featured image path must be inside /images or /storage.');
                    }
                },
            ],
            'remove_featured_image' => ['sometimes', 'boolean'],
        ]);
    }

    private function normalizeValidatedPostData(array $validated): array
    {
        $validated['title'] = $this->cleanInlineText((string) ($validated['title'] ?? ''));
        $validated['slug'] = isset($validated['slug']) ? $this->cleanInlineText((string) $validated['slug']) : null;
        $validated['excerpt'] = isset($validated['excerpt']) ? trim((string) $validated['excerpt']) : null;
        $validated['sources'] = isset($validated['sources']) ? trim(strip_tags((string) $validated['sources'])) : null;
        $validated['meta_title'] = isset($validated['meta_title']) ? $this->cleanInlineText((string) $validated['meta_title']) : null;
        $validated['meta_description'] = isset($validated['meta_description']) ? trim(strip_tags((string) $validated['meta_description'])) : null;
        $validated['canonical_url'] = isset($validated['canonical_url']) ? trim((string) $validated['canonical_url']) : null;
        $validated['og_title'] = isset($validated['og_title']) ? $this->cleanInlineText((string) $validated['og_title']) : null;
        $validated['og_description'] = isset($validated['og_description']) ? trim(strip_tags((string) $validated['og_description'])) : null;
        $validated['og_image_path'] = isset($validated['og_image_path'])
            ? ($this->normalizeMediaPath((string) $validated['og_image_path']) ?? trim((string) $validated['og_image_path']))
            : null;
        $validated['new_category'] = isset($validated['new_category']) ? $this->cleanInlineText((string) $validated['new_category']) : null;
        $validated['featured_image_path'] = isset($validated['featured_image_path'])
            ? ($this->normalizeMediaPath((string) $validated['featured_image_path']) ?? trim((string) $validated['featured_image_path']))
            : null;

        if (isset($validated['new_tags']) && is_array($validated['new_tags'])) {
            $validated['new_tags'] = array_values(array_filter(array_map(
                fn ($tag) => $this->cleanInlineText((string) $tag),
                $validated['new_tags']
            )));
        }

        $validated['noindex'] = (bool) ($validated['noindex'] ?? false);
        $validated['is_featured'] = (bool) ($validated['is_featured'] ?? false);

        return $validated;
    }

    private function cleanInlineText(string $value): string
    {
        return Str::squish(strip_tags($value));
    }

    private function assertSafeUploadedImage(UploadedFile $file): void
    {
        $realPath = $file->getRealPath();

        if ($realPath === false) {
            abort(422, 'Invalid uploaded image.');
        }

        $imageInfo = @getimagesize($realPath);

        if ($imageInfo === false || !isset($imageInfo['mime'])) {
            abort(422, 'Uploaded file is not a valid image.');
        }

        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        if (!in_array((string) $imageInfo['mime'], $allowedMimes, true)) {
            abort(422, 'Unsupported image type.');
        }
    }

    private function generateUniqueSlug(string $input, ?int $ignoreId = null): string
    {
        $base = Str::slug($input);

        if ($base === '') {
            $base = 'post';
        }

        $existing = Post::query()
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where('slug', 'like', $base . '%')
            ->pluck('slug');

        if (!$existing->contains($base)) {
            return $base;
        }

        $counter = 2;

        do {
            $candidate = $base . '-' . $counter;
            $counter++;
        } while ($existing->contains($candidate));

        return $candidate;
    }

    private function buildPostNavigator(Post $post): array
{
    $previous = Post::query()
        ->whereNull('archived_at')
        ->where('id', '>', $post->id)
        ->orderBy('id')
        ->first(['id', 'title']);

    if (! $previous) {
        $previous = Post::query()
            ->whereNull('archived_at')
            ->whereKeyNot($post->id)
            ->orderBy('id')
            ->first(['id', 'title']);
    }

    $next = Post::query()
        ->whereNull('archived_at')
        ->where('id', '<', $post->id)
        ->orderByDesc('id')
        ->first(['id', 'title']);

    if (! $next) {
        $next = Post::query()
            ->whereNull('archived_at')
            ->whereKeyNot($post->id)
            ->orderByDesc('id')
            ->first(['id', 'title']);
    }

    return [
        'previous' => $previous
            ? [
                'id' => $previous->id,
                'title' => $previous->title,
            ]
            : null,
        'next' => $next
            ? [
                'id' => $next->id,
                'title' => $next->title,
            ]
            : null,
    ];
}

    private function applyPostIndexOrdering($query)
{
    return $query
        ->orderByDesc('id');
}

    private function storeImageInBlogLibrary(UploadedFile $file, string $baseName): string
    {
        $directory = public_path('images/blog');

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $filename = Str::slug($baseName) ?: 'post-image';
        $candidate = $filename . '.' . $extension;
        $counter = 2;

        while (File::exists($directory . DIRECTORY_SEPARATOR . $candidate)) {
            $candidate = $filename . '-' . $counter . '.' . $extension;
            $counter++;
        }

        $file->move($directory, $candidate);

        return '/images/blog/' . $candidate;
    }

    private function normalizeMediaPath(?string $path): ?string
    {
        return Post::normalizeManagedImagePath($path);
    }

    private function resolveMediaUrl(?string $path): ?string
    {
        return Post::resolveImageUrl($path);
    }

    private function logAdminAction(string $event, Request $request, Post $post, array $extra = []): void
    {
        Log::channel(config('logging.default'))->info($event, [
            'event' => $event,
            'user_id' => (int) $request->user()->id,
            'post_id' => $post->id,
            'post_slug' => $post->slug,
            'post_status' => $post->status,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'occurred_at' => now()->toIso8601String(),
            ...$extra,
        ]);
    }
}
