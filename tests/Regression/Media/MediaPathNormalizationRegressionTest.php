<?php

namespace Tests\Regression\Media;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MediaPathNormalizationRegressionTest extends TestCase
{
    use RefreshDatabase;

    private string $blogImagesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blogImagesRoot = public_path('images/blog');

        File::ensureDirectoryExists($this->blogImagesRoot);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->blogImagesRoot . DIRECTORY_SEPARATOR . 'normalization-*') ?: [] as $file) {
            if (is_file($file)) {
                File::delete($file);
            }
        }

        parent::tearDown();
    }

    public function test_delete_guard_blocks_all_supported_managed_path_variants_when_posts_still_reference_the_image(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $columns = ['featured_image_path', 'og_image_path'];

        foreach ($columns as $column) {
            foreach ($this->managedPathVariants('normalization-delete-' . $column) as $variant) {
                $canonicalPath = '/images/blog/normalization-delete-' . $column . '.png';
                File::put($this->blogImagesRoot . DIRECTORY_SEPARATOR . 'normalization-delete-' . $column . '.png', 'image');

                Post::factory()->create([$column => $variant]);

                $this->actingAs($admin)
                    ->deleteJson(route('admin.media.destroy'), ['path' => $canonicalPath])
                    ->assertStatus(422)
                    ->assertJsonPath('message', 'This image is still being used by a post.');

                $this->assertFileExists(
                    $this->blogImagesRoot . DIRECTORY_SEPARATOR . 'normalization-delete-' . $column . '.png'
                );

                Post::query()->delete();
                File::delete($this->blogImagesRoot . DIRECTORY_SEPARATOR . 'normalization-delete-' . $column . '.png');
            }
        }
    }

    public function test_post_save_normalizes_all_supported_managed_path_variants_to_the_canonical_images_path(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::factory()->create();

        foreach (array_values($this->managedPathTemplates()) as $index => $template) {
            $title = 'Normalization Variant ' . ($index + 1);

            $this->actingAs($admin)
                ->post(route('admin.posts.store'), [
                    'title' => $title,
                    'content' => '<p>Body</p>',
                    'category_id' => $category->id,
                    'featured_image_path' => sprintf($template, 'normalization-featured-' . $index),
                    'og_image_path' => sprintf($template, 'normalization-og-' . $index),
                ])
                ->assertRedirect();

            $post = Post::query()->where('title', $title)->firstOrFail();

            $this->assertSame('/images/blog/normalization-featured-' . $index . '.png', $post->featured_image_path);
            $this->assertSame('/images/blog/normalization-og-' . $index . '.png', $post->og_image_path);
        }
    }

    private function managedPathVariants(string $basename): array
    {
        return array_map(
            fn (string $template) => sprintf($template, $basename),
            $this->managedPathTemplates()
        );
    }

    private function managedPathTemplates(): array
    {
        return [
            '/images/blog/%s.png',
            'images/blog/%s.png',
            '/storage/images/blog/%s.png',
            'storage/images/blog/%s.png',
        ];
    }
}
