<?php

namespace Tests\Feature\Admin;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MediaLibraryDeletionTest extends TestCase
{
    use RefreshDatabase;

    private string $imagesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        // ✅ USE TEST-ONLY DIRECTORY (NOT PUBLIC)
        $this->imagesRoot = storage_path('framework/testing/images/blog');

        File::ensureDirectoryExists($this->imagesRoot);
    }

    protected function tearDown(): void
    {
        // ✅ SAFE: only deletes test directory
        File::deleteDirectory(storage_path('framework/testing'));

        parent::tearDown();
    }

    private function testPath(string $filename): string
    {
        return $this->imagesRoot . DIRECTORY_SEPARATOR . $filename;
    }

    private function publicPath(string $filename): string
    {
        // This mimics how your app references images
        return '/images/blog/' . $filename;
    }

    public function test_non_images_paths_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.media.destroy'), [
                'path' => '/storage/private/file.txt',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only files inside /images can be deleted.');
    }

    public function test_featured_images_in_use_by_posts_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $file = 'in-use-featured.png';
        File::put($this->testPath($file), 'image');

        $path = $this->publicPath($file);

        Post::factory()->create(['featured_image_path' => $path]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.media.destroy'), ['path' => $path])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This image is still being used by a post.');

        $this->assertFileExists($this->testPath($file));
    }

    public function test_og_images_in_use_by_posts_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $file = 'in-use-og.png';
        File::put($this->testPath($file), 'image');

        $path = $this->publicPath($file);

        Post::factory()->create(['og_image_path' => $path]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.media.destroy'), ['path' => $path])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This image is still being used by a post.');

        $this->assertFileExists($this->testPath($file));
    }

    public function test_storage_style_featured_image_paths_still_block_media_deletion(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $file = 'in-use-storage.png';
        File::put($this->testPath($file), 'image');

        $path = $this->publicPath($file);

        Post::factory()->create([
            'featured_image_path' => '/storage/images/blog/' . $file
        ]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.media.destroy'), ['path' => $path])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This image is still being used by a post.');

        $this->assertFileExists($this->testPath($file));
    }
}
