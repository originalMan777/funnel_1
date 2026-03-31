<?php

namespace Tests\Regression\FailureModes;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiImporterInvalidPackageRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_importer_rejects_packages_that_fail_the_controller_structure_gate(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.post-importer.index'))
            ->post(route('admin.post-importer.store'), [
                'package' => $this->missingListPackage(),
            ])
            ->assertRedirect(route('admin.post-importer.index'))
            ->assertSessionHasErrors([
                'package' => 'Invalid AI package structure.',
            ]);

        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('tags', 0);
    }

    public function test_importer_rejects_known_bad_package_shapes_without_partial_persistence(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.post-importer.index'))
            ->post(route('admin.post-importer.store'), [
                'package' => $this->wrongOrderPackage(),
            ])
            ->assertRedirect(route('admin.post-importer.index'))
            ->assertSessionHasErrors([
                'package' => 'Failed to process AI package.',
            ]);

        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('tags', 0);
    }

    private function missingListPackage(): string
    {
        return 'Not a valid importer package.';
    }

    private function wrongOrderPackage(): string
    {
        return <<<'TEXT'
TITLE:
Importer Wrong Order Package

ARTICLE:
This malformed package is long enough to pass the controller check safely.

It should still fail before any content is persisted because the LIST order is wrong.

LIST:
- Slug: importer-wrong-order-package
- SEO Title: Importer Wrong Order Package
- Excerpt: This malformed package should never persist.
- Sources: Freddie Mac PMMS
- Category: Buyers
- Tags: buyer mistakes, bad package
- Meta Title: Importer Wrong Order Package
- Meta Description: This malformed package should never persist.
- Canonical URL: https://www.example.com/blog/importer-wrong-order-package
- OG Title: Importer Wrong Order Package
- OG Description: This malformed package should never persist.
- Featured Image Path: /images/blog/importer-wrong-order-package.jpg
- OG Image Path: /images/blog/importer-wrong-order-package-og.jpg
- Noindex: No
TEXT;
    }
}
