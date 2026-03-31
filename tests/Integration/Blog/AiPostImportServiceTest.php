<?php

namespace Tests\Integration\Blog;

use App\Models\Post;
use App\Models\User;
use App\Services\Blog\AiPostImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AiPostImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_imports_a_sanitized_draft_post_with_taxonomy_and_audit_fields(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $post = app(AiPostImportService::class)->import($this->sanitizedPackage(), $user);
        $post->load(['category', 'tags']);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertSame(Post::STATUS_DRAFT, $post->status);
        $this->assertNull($post->published_at);
        $this->assertSame($user->id, $post->created_by);
        $this->assertSame($user->id, $post->updated_by);
        $this->assertSame('Buyers', $post->category?->name);
        $this->assertCount(3, $post->tags);
        $this->assertSame('Buyer Mistakes: A Strategy Breakdown for Winning Smarter in Today\'s Market', $post->title);
        $this->assertSame('buyer-mistakes-strategies', $post->slug);
        $this->assertStringContainsString('<p>Buying a home is exciting for a reason.</p>', $post->content);
        $this->assertStringNotContainsString('<script', $post->content);
        $this->assertStringNotContainsString('javascript:', $post->content);
        $this->assertSame('/images/blog/buyer-mistakes-strategies-cover.jpg', $post->featured_image_path);
        $this->assertSame('/images/blog/buyer-mistakes-strategies-og.jpg', $post->og_image_path);
        $this->assertSame('https://www.example.com/blog/buyer-mistakes-strategies', $post->canonical_url);
        $this->assertFalse($post->noindex);
    }

    public function test_service_rejects_an_import_when_the_requested_slug_is_already_taken(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        Post::factory()->create(['slug' => 'buyer-mistakes-strategies']);

        $this->expectException(ValidationException::class);

        app(AiPostImportService::class)->import($this->validPackage(), $user);
    }

    public function test_service_rejects_oversized_packages_before_any_write(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->expectException(ValidationException::class);

        app(AiPostImportService::class)->import(str_repeat('A', 250001), $user);
    }

    public function test_service_allows_empty_optional_image_fields_and_persists_them_as_null(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $post = app(AiPostImportService::class)->import($this->validPackageWithEmptyImages(), $user);

        $this->assertNull($post->featured_image_path);
        $this->assertNull($post->og_image_path);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => Post::STATUS_DRAFT,
            'featured_image_path' => null,
            'og_image_path' => null,
        ]);
    }

    public function test_service_rejects_invalid_image_paths_without_persisting_post_or_taxonomy(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        try {
            app(AiPostImportService::class)->import($this->invalidImagePackage(), $user);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('image', $exception->errors());
        }

        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseMissing('categories', ['slug' => 'buyers']);
        $this->assertDatabaseMissing('tags', ['slug' => 'buyer-mistakes']);
    }

    public function test_service_rejects_invalid_tag_input_without_persisting_any_rows(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        try {
            app(AiPostImportService::class)->import($this->invalidTagPackage(), $user);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('tags', $exception->errors());
        }

        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('tags', 0);
    }

    private function sanitizedPackage(): string
    {
        return <<<'TEXT'
TITLE:
<b>Buyer Mistakes: A Strategy Breakdown for Winning Smarter in Today's Market</b>

ARTICLE:
<p>Buying a home is exciting for a reason.</p>

<p>It feels like progress, possibility, and a real move forward.</p>

<p><a href="#" onclick="alert('bad')">Unsafe Link</a> but still enough safe content to stay above the sanitizer threshold.</p>

LIST:
- SEO Title: Buyer Mistakes to Avoid: Smart Strategies for Today's Market
- Slug: buyer-mistakes-strategies
- Excerpt: Buyers have more room to negotiate in today's market, but that does not mean mistakes have disappeared.
- Sources: Freddie Mac PMMS; National Association of Realtors; Consumer Financial Protection Bureau
- Category: <strong>Buyers</strong>
- Tags: buyer mistakes, home buying strategy, first-time home buyers
- Meta Title: Buyer Mistakes to Avoid in 2026 | Smart Home Buying Strategies
- Meta Description: Learn the biggest buyer mistakes in today's housing market and how to avoid them.
- Canonical URL: https://www.example.com/blog/buyer-mistakes-strategies
- OG Title: Buyer Mistakes to Avoid: A Smart Strategy Breakdown
- OG Description: A clear, practical breakdown of common buyer mistakes.
- Featured Image Path: /images/blog/buyer-mistakes-strategies-cover.jpg
- OG Image Path: /images/blog/buyer-mistakes-strategies-og.jpg
- Noindex: No
TEXT;
    }

    private function validPackage(): string
    {
        return <<<'TEXT'
TITLE:
Buyer Mistakes: A Strategy Breakdown for Winning Smarter in Today's Market

ARTICLE:
<p>Buying a home is exciting for a reason.</p>

<p>It feels like progress, possibility, and a real move forward.</p>

LIST:
- SEO Title: Buyer Mistakes to Avoid: Smart Strategies for Today's Market
- Slug: buyer-mistakes-strategies
- Excerpt: Buyers have more room to negotiate in today's market, but that does not mean mistakes have disappeared.
- Sources: Freddie Mac PMMS; National Association of Realtors; Consumer Financial Protection Bureau
- Category: Buyers
- Tags: buyer mistakes, home buying strategy, first-time home buyers
- Meta Title: Buyer Mistakes to Avoid in 2026 | Smart Home Buying Strategies
- Meta Description: Learn the biggest buyer mistakes in today's housing market and how to avoid them.
- Canonical URL: https://www.example.com/blog/buyer-mistakes-strategies
- OG Title: Buyer Mistakes to Avoid: A Smart Strategy Breakdown
- OG Description: A clear, practical breakdown of common buyer mistakes.
- Featured Image Path: /images/blog/buyer-mistakes-strategies-cover.jpg
- OG Image Path: /images/blog/buyer-mistakes-strategies-og.jpg
- Noindex: No
TEXT;
    }

    private function validPackageWithEmptyImages(): string
    {
        return <<<'TEXT'
TITLE:
Buyer Mistakes Without Images

ARTICLE:
Buying a home is exciting for a reason.

It feels like progress, possibility, and a real move forward.

This version intentionally leaves optional image fields empty while still meeting the minimum article length.

LIST:
- SEO Title: Buyer Mistakes Without Images
- Slug: buyer-mistakes-without-images
- Excerpt: This package proves optional image fields can remain empty.
- Sources: Freddie Mac PMMS; National Association of Realtors
- Category: Buyers
- Tags: buyer mistakes, home buying strategy
- Meta Title: Buyer Mistakes Without Images
- Meta Description: This package proves optional image fields can remain empty.
- Canonical URL: https://www.example.com/blog/buyer-mistakes-without-images
- OG Title: Buyer Mistakes Without Images
- OG Description: This package proves optional image fields can remain empty.
- Featured Image Path:
- OG Image Path:
- Noindex: No
TEXT;
    }

    private function invalidImagePackage(): string
    {
        return <<<'TEXT'
TITLE:
Buyer Mistakes

ARTICLE:
Buying a home is exciting for a reason.

It feels like progress, possibility, and a real move forward.

This article has enough content to pass the minimum length validation.

LIST:
- SEO Title: Buyer Mistakes to Avoid
- Slug: buyer-mistakes
- Excerpt: Buyers have more room to negotiate in today's market.
- Sources: Freddie Mac PMMS; National Association of Realtors
- Category: Buyers
- Tags: buyer mistakes, home buying strategy
- Meta Title: Buyer Mistakes to Avoid in 2026
- Meta Description: Learn the biggest buyer mistakes in today's housing market and how to avoid them.
- Canonical URL: https://www.example.com/blog/buyer-mistakes
- OG Title: Buyer Mistakes to Avoid
- OG Description: A clear breakdown of common buyer mistakes.
- Featured Image Path: ../escape.png
- OG Image Path: /images/blog/ok.png
- Noindex: No
TEXT;
    }

    private function invalidTagPackage(): string
    {
        return <<<'TEXT'
TITLE:
Buyer Mistakes With Invalid Tags

ARTICLE:
Buying a home is exciting for a reason.

It feels like progress, possibility, and a real move forward.

This article is long enough to reach service-level tag validation safely.

LIST:
- SEO Title: Buyer Mistakes With Invalid Tags
- Slug: buyer-mistakes-invalid-tags
- Excerpt: This package should fail because one tag uses an invalid format.
- Sources: Freddie Mac PMMS; National Association of Realtors
- Category: Buyers
- Tags: buyer mistakes, bad/tag
- Meta Title: Buyer Mistakes With Invalid Tags
- Meta Description: This package should fail because one tag uses an invalid format.
- Canonical URL: https://www.example.com/blog/buyer-mistakes-invalid-tags
- OG Title: Buyer Mistakes With Invalid Tags
- OG Description: This package should fail because one tag uses an invalid format.
- Featured Image Path: /images/blog/buyer-mistakes-invalid-tags.jpg
- OG Image Path: /images/blog/buyer-mistakes-invalid-tags-og.jpg
- Noindex: No
TEXT;
    }
}
