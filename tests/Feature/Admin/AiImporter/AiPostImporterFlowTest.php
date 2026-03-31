<?php

namespace Tests\Feature\Admin\AiImporter;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AiPostImporterFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_importer_routes(): void
    {
        $this->get(route('admin.post-importer.index'))
            ->assertRedirect(route('login'));

        $this->post(route('admin.post-importer.store'), [
            'package' => $this->validPackage(),
        ])->assertRedirect(route('login'));
    }

    public function test_non_authorized_users_are_forbidden_from_importer_routes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.post-importer.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.post-importer.store'), [
                'package' => $this->validPackage(),
            ])
            ->assertForbidden();
    }

    public function test_authorized_admin_can_access_the_importer_index_flow(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.post-importer.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PostImporter/Index')
            );
    }

    public function test_valid_import_submission_creates_a_draft_post_and_redirects_to_edit(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->post(route('admin.post-importer.store'), [
                'package' => $this->validPackage(),
            ]);

        $post = Post::query()->with(['category', 'tags'])->sole();

        $response->assertRedirect(route('admin.posts.edit', $post));
        $response->assertSessionHas('success', 'Post imported as draft.');

        $this->assertSame(Post::STATUS_DRAFT, $post->status);
        $this->assertNull($post->published_at);
        $this->assertSame($admin->id, $post->created_by);
        $this->assertSame($admin->id, $post->updated_by);
        $this->assertSame('buyer-mistakes-strategies', $post->slug);
        $this->assertSame('Buyers', $post->category?->name);
        $this->assertCount(3, $post->tags);
        $this->assertSame('/images/blog/buyer-mistakes-strategies-cover.jpg', $post->featured_image_path);
    }

    public function test_malformed_importer_submission_is_rejected_cleanly_without_creating_a_post(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.post-importer.index'))
            ->post(route('admin.post-importer.store'), [
                'package' => $this->malformedPackage(),
            ])
            ->assertRedirect(route('admin.post-importer.index'))
            ->assertSessionHasErrors([
                'package' => 'Failed to process AI package.',
            ]);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_blank_or_invalid_payloads_do_not_silently_succeed(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.post-importer.index'))
            ->post(route('admin.post-importer.store'), [
                'package' => '   ',
            ])
            ->assertRedirect(route('admin.post-importer.index'))
            ->assertSessionHasErrors([
                'package' => 'The package field is required.',
            ]);

        $this->assertDatabaseCount('posts', 0);
    }

    private function malformedPackage(): string
    {
        return <<<'TEXT'
TITLE:
Broken Import Package

ARTICLE:
This article is long enough to get past the controller structure check.

It still uses the wrong LIST order so the parser should reject it safely.

LIST:
- Slug: broken-import-package
- SEO Title: Broken Import Package
- Excerpt: This malformed package should never create a post.
- Sources: Freddie Mac PMMS
- Category: Buyers
- Tags: buyer mistakes, bad package
- Meta Title: Broken Import Package
- Meta Description: This malformed package should be rejected.
- Canonical URL: https://www.example.com/blog/broken-import-package
- OG Title: Broken Import Package
- OG Description: Rejected malformed importer package.
- Featured Image Path: /images/blog/broken-import-package.jpg
- OG Image Path: /images/blog/broken-import-package-og.jpg
- Noindex: No
TEXT;
    }

    private function validPackage(): string
    {
        return <<<'TEXT'
TITLE:
Buyer Mistakes: A Strategy Breakdown for Winning Smarter in Today's Market

ARTICLE:
Buying a home is exciting for a reason.

It feels like progress, possibility, and a real move forward.

This package has enough article content to satisfy the importer validation rules safely.

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
}
