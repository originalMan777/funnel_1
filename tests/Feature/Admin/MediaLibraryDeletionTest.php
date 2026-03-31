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

        $this->imagesRoot = public_path('images/blog');
        File::ensureDirectoryExists($this->imagesRoot);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->imagesRoot);

        parent::tearDown();
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
        $path = '/images/blog/in-use-featured.png';
        File::put($this->imagesRoot . DIRECTORY_SEPARATOR . 'in-use-featured.png', 'image');

        Post::factory()->create(['featured_image_path' => $path]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.media.destroy'), ['path' => $path])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This image is still being used by a post.');

        $this->assertFileExists($this->imagesRoot . DIRECTORY_SEPARATOR . 'in-use-featured.png');
    }

    public function test_og_images_in_use_by_posts_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $path = '/images/blog/in-use-og.png';
        File::put($this->imagesRoot . DIRECTORY_SEPARATOR . 'in-use-og.png', 'image');

        Post::factory()->create(['og_image_path' => $path]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.media.destroy'), ['path' => $path])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This image is still being used by a post.');

        $this->assertFileExists($this->imagesRoot . DIRECTORY_SEPARATOR . 'in-use-og.png');
    }

    public function test_storage_style_featured_image_paths_still_block_media_deletion(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $path = '/images/blog/in-use-storage.png';
        File::put($this->imagesRoot . DIRECTORY_SEPARATOR . 'in-use-storage.png', 'image');

        Post::factory()->create(['featured_image_path' => '/storage/images/blog/in-use-storage.png']);

        $this->actingAs($admin)
            ->deleteJson(route('admin.media.destroy'), ['path' => $path])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This image is still being used by a post.');

        $this->assertFileExists($this->imagesRoot . DIRECTORY_SEPARATOR . 'in-use-storage.png');
    }
}
