<?php

namespace App\Services\Blog;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AiPostImportService
{
    public function __construct(
        private readonly AiPostPackageParser $parser,
        private readonly PostContentSanitizer $sanitizer,
    ) {
    }

    public function import(string $package, User $user): Post
    {
        /*
        |--------------------------------------------------------------------------
        | 🔒 LAYER 1: HARD INPUT LIMIT
        |--------------------------------------------------------------------------
        */
        if (mb_strlen($package) > 250_000) {
            throw ValidationException::withMessages([
                'package' => 'AI payload too large.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 🔒 LAYER 2: PARSE (ZERO TRUST)
        |--------------------------------------------------------------------------
        */
        $parsed = $this->parser->parse($package);

        if (!is_array($parsed) || empty($parsed)) {
            throw ValidationException::withMessages([
                'package' => 'Invalid AI package format.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 🔒 LAYER 3: VALIDATION
        |--------------------------------------------------------------------------
        */
        $validated = $this->validateParsed($parsed);

        return DB::transaction(function () use ($validated, $user, $package) {

            /*
            |--------------------------------------------------------------------------
            | 🔒 CATEGORY HARDENING
            |--------------------------------------------------------------------------
            */
            $safeCategory = $this->cleanTaxonomyString($validated['category']);

            if ($safeCategory === '' || mb_strlen($safeCategory) > 120) {
                throw ValidationException::withMessages([
                    'category' => 'Invalid category.',
                ]);
            }

            $category = Category::firstOrCreate(
                ['slug' => Str::slug($safeCategory)],
                ['name' => $safeCategory]
            );

            /*
            |--------------------------------------------------------------------------
            | 🔒 TAG HARDENING (STRICT CONTROL)
            |--------------------------------------------------------------------------
            */
            $tagIds = collect($validated['tags'])
                ->map(fn ($tag) => $this->cleanTaxonomyString((string) $tag))
                ->filter(fn ($tag) => $tag !== '')
                ->map(function (string $tagName) {

                    if (mb_strlen($tagName) > 80) {
                        throw ValidationException::withMessages([
                            'tags' => 'Tag too long.',
                        ]);
                    }

                    if (!preg_match('/^[\pL\pN\s\-]+$/u', $tagName)) {
                        throw ValidationException::withMessages([
                            'tags' => 'Invalid tag format.',
                        ]);
                    }

                    $tag = Tag::firstOrCreate(
                        ['slug' => Str::slug($tagName)],
                        ['name' => $tagName]
                    );

                    return $tag->id;
                })
                ->unique()
                ->take(12)
                ->values()
                ->all();

            /*
            |--------------------------------------------------------------------------
            | 🔒 CONTENT SANITIZATION (FINAL GATE)
            |--------------------------------------------------------------------------
            */
            $cleanContent = $this->sanitizer->normalizeAndSanitizeArticle(
                $this->stripDangerousPatterns($validated['article'])
            );

            if ($cleanContent === '' || mb_strlen($cleanContent) < 40) {
                throw ValidationException::withMessages([
                    'content' => 'Sanitized content is invalid.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 🔒 URL HARDENING
            |--------------------------------------------------------------------------
            */
            $canonicalUrl = $this->sanitizeUrl($validated['canonical_url']);

            /*
            |--------------------------------------------------------------------------
            | 🔒 IMAGE PATH HARDENING
            |--------------------------------------------------------------------------
            */
            $featuredImage = $this->validateImagePath($validated['featured_image_path']);
            $ogImage = $this->validateImagePath($validated['og_image_path']);

            /*
            |--------------------------------------------------------------------------
            | 🔒 SLUG HARDENING
            |--------------------------------------------------------------------------
            */
            $slug = $this->generateUniqueSlug($validated['slug']);

            /*
            |--------------------------------------------------------------------------
            | 🔒 CREATE POST
            |--------------------------------------------------------------------------
            */
            $post = Post::create([
                'title' => $this->cleanString($validated['title']),
                'slug' => $slug,
                'excerpt' => $this->cleanString($validated['excerpt']),
                'content' => $cleanContent,
                'sources' => $this->cleanString($validated['sources']),
                'featured_image_path' => $featuredImage,
                'status' => Post::STATUS_DRAFT,
                'published_at' => null,
                'meta_title' => $this->cleanString($validated['meta_title']),
                'meta_description' => $this->cleanString($validated['meta_description']),
                'canonical_url' => $canonicalUrl,
                'og_title' => $this->cleanString($validated['og_title']),
                'og_description' => $this->cleanString($validated['og_description']),
                'og_image_path' => $ogImage,
                'noindex' => $validated['noindex'],
                'category_id' => $category->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $post->tags()->sync($tagIds);

            /*
            |--------------------------------------------------------------------------
            | 🔒 ENTERPRISE AUDIT LOG
            |--------------------------------------------------------------------------
            */
            Log::channel(config('logging.default'))->info('ai_post_imported', [
                'event' => 'ai_post_imported',
                'user_id' => $user->id,
                'post_id' => $post->id,
                'slug' => $post->slug,
                'ip' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 500),
                'payload_size' => mb_strlen($package),
                'content_length' => mb_strlen($cleanContent),
                'tag_count' => count($tagIds),
                'occurred_at' => now()->toIso8601String(),
            ]);

            return $post;
        });
    }

    private function validateParsed(array $parsed): array
    {
        return Validator::make($parsed, [
            'title' => ['required', 'string', 'max:255'],
            'article' => ['required', 'string', 'min:40', 'max:200000'],
            'seo_title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('posts', 'slug'),
            ],
            'excerpt' => ['required', 'string', 'max:1000'],
            'sources' => ['required', 'string', 'max:1000'],
            'category' => ['required', 'string', 'max:120'],
            'tags' => ['required', 'array', 'min:1', 'max:12'],
            'tags.*' => ['required', 'string', 'max:80'],
            'meta_title' => ['required', 'string', 'max:255'],
            'meta_description' => ['required', 'string', 'max:320'],
            'canonical_url' => ['required', 'string', 'max:2048'],
            'og_title' => ['required', 'string', 'max:255'],
            'og_description' => ['required', 'string', 'max:320'],
            'featured_image_path' => ['nullable', 'string', 'max:2048'],
            'og_image_path' => ['nullable', 'string', 'max:2048'],
            'noindex' => ['required', 'boolean'],
        ])->validate();
    }

    private function stripDangerousPatterns(string $content): string
    {
        return preg_replace([
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/on\w+\s*=\s*"[^"]*"/i',
            '/on\w+\s*=\s*\'[^\']*\'/i',
            '/javascript:/i',
            '/data:text\/html/i',
        ], '', $content) ?? $content;
    }

    private function sanitizeUrl(string $url): string
    {
        $url = trim($url);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                'canonical_url' => 'Invalid URL.',
            ]);
        }

        $lower = Str::lower($url);

        if (!Str::startsWith($lower, ['http://', 'https://'])) {
            throw ValidationException::withMessages([
                'canonical_url' => 'Invalid protocol.',
            ]);
        }

        return $url;
    }

    private function validateImagePath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (str_contains($path, '..')) {
            throw ValidationException::withMessages([
                'image' => 'Invalid image path.',
            ]);
        }

        if (!preg_match('#^/images/[A-Za-z0-9/_\-.]+$#', $path)) {
            throw ValidationException::withMessages([
                'image' => 'Invalid image path.',
            ]);
        }

        return $path;
    }

    private function cleanString(string $value): string
    {
        return trim(strip_tags($value));
    }

    private function cleanTaxonomyString(string $value): string
    {
        return Str::squish(strip_tags(trim($value)));
    }

    private function generateUniqueSlug(string $baseSlug): string
    {
        $slug = Str::slug($baseSlug);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'slug' => 'Invalid slug.',
            ]);
        }

        $candidate = $slug;
        $counter = 2;

        while (Post::where('slug', $candidate)->exists()) {
            $candidate = $slug . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }
}
