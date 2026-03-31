<?php

namespace Tests\Regression\FailureModes;

use App\Models\Post;
use App\Models\User;
use App\Services\Blog\AiPostImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AiImporterPartialWriteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_imports_do_not_leave_half_created_posts_categories_tags_or_pivot_rows(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $service = app(AiPostImportService::class);

        try {
            $service->import($this->invalidImagePackage(), $user);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('image', $exception->errors());
        }

        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('tags', 0);
        $this->assertDatabaseCount('post_tag', 0);
    }

    public function test_successful_import_persists_a_complete_and_coherent_draft_state(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $post = app(AiPostImportService::class)->import($this->validPackage(), $user);

        $post->load(['category', 'tags']);

        $this->assertSame(Post::STATUS_DRAFT, $post->status);
        $this->assertNull($post->published_at);
        $this->assertSame('Buyers', $post->category?->name);
        $this->assertCount(2, $post->tags);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'slug' => 'ai-import-complete-state',
            'status' => Post::STATUS_DRAFT,
            'category_id' => $post->category_id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        foreach ($post->tags as $tag) {
            $this->assertDatabaseHas('post_tag', [
                'post_id' => $post->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    private function invalidImagePackage(): string
    {
        return <<<'TEXT'
TITLE:
Importer Partial Write Protection

ARTICLE:
Buying a home is exciting for a reason.

It feels like progress, possibility, and a real move forward.

This package is long enough that the import reaches transactional validation.

LIST:
- SEO Title: Importer Partial Write Protection
- Slug: importer-partial-write-protection
- Excerpt: This package should fail before any rows persist.
- Sources: Freddie Mac PMMS; National Association of Realtors
- Category: Buyers
- Tags: buyer mistakes, home buying strategy
- Meta Title: Importer Partial Write Protection
- Meta Description: This package should fail before any rows persist.
- Canonical URL: https://www.example.com/blog/importer-partial-write-protection
- OG Title: Importer Partial Write Protection
- OG Description: This package should fail before any rows persist.
- Featured Image Path: ../escape.png
- OG Image Path: /images/blog/importer-partial-write-protection-og.jpg
- Noindex: No
TEXT;
    }

    private function validPackage(): string
    {
        return <<<'TEXT'
TITLE:
AI Import Complete State

ARTICLE:
Buying a home is exciting for a reason.

It feels like progress, possibility, and a real move forward.

This package proves a successful import leaves behind a complete coherent draft state.

LIST:
- SEO Title: AI Import Complete State
- Slug: ai-import-complete-state
- Excerpt: This package proves a successful import leaves behind a complete coherent draft state.
- Sources: Freddie Mac PMMS; National Association of Realtors
- Category: Buyers
- Tags: buyer mistakes, home buying strategy
- Meta Title: AI Import Complete State
- Meta Description: This package proves a successful import leaves behind a complete coherent draft state.
- Canonical URL: https://www.example.com/blog/ai-import-complete-state
- OG Title: AI Import Complete State
- OG Description: This package proves a successful import leaves behind a complete coherent draft state.
- Featured Image Path: /images/blog/ai-import-complete-state.jpg
- OG Image Path: /images/blog/ai-import-complete-state-og.jpg
- Noindex: No
TEXT;
    }
}
