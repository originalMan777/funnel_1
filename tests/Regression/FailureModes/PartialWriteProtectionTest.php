<?php

namespace Tests\Regression\FailureModes;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\Blog\AiPostImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PartialWriteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_post_store_does_not_leave_partial_taxonomy_or_post_writes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.posts.create'))
            ->post(route('admin.posts.store'), [
                'title' => 'Bad write',
                'content' => '<p>Body</p>',
                'sources' => 'https://example.com/source',
                'new_category' => 'Should Not Persist',
                'new_tags' => ['Should Not Persist'],
                'featured_image_path' => 'https://evil.example.com/image.png',
            ])
            ->assertRedirect(route('admin.posts.create'))
            ->assertSessionHasErrors('featured_image_path');

        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseMissing('categories', ['name' => 'Should Not Persist']);
        $this->assertDatabaseMissing('tags', ['name' => 'Should Not Persist']);
    }

    public function test_ai_import_transaction_rolls_back_partial_taxonomy_writes_on_failure(): void
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
        $this->assertDatabaseMissing('categories', ['slug' => 'buyers']);
        $this->assertDatabaseMissing('tags', ['slug' => 'buyer-mistakes']);
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
}
