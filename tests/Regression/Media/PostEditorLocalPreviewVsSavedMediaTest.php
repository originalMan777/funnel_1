<?php

namespace Tests\Regression\Media;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PostEditorLocalPreviewVsSavedMediaTest extends TestCase
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
        foreach ([
            'local-preview-boundary-post.png',
            'existing-saved-library.png',
        ] as $filename) {
            File::delete($this->blogImagesRoot . DIRECTORY_SEPARATOR . $filename);
        }

        parent::tearDown();
    }

    public function test_local_editor_file_is_not_saved_in_the_media_library_until_the_post_is_saved(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::factory()->create();
        $expectedPath = '/images/blog/local-preview-boundary-post.png';

        $beforeSavePaths = collect(
            $this->actingAs($admin)->getJson(route('admin.media.feed', [
                'folder' => 'blog',
                'page' => 1,
                'per_page' => 24,
                'search' => '',
            ]))->json('media.data')
        )->pluck('path')->all();

        $this->assertNotContains($expectedPath, $beforeSavePaths);
        $this->assertFileDoesNotExist($this->blogImagesRoot . DIRECTORY_SEPARATOR . 'local-preview-boundary-post.png');

        $this->actingAs($admin)
            ->post(route('admin.posts.store'), [
                'title' => 'Local Preview Boundary Post',
                'content' => '<p>Body</p>',
                'category_id' => $category->id,
                'featured_image' => UploadedFile::fake()->image('local-only-preview.png'),
            ])
            ->assertRedirect();

        $post = Post::query()->where('title', 'Local Preview Boundary Post')->firstOrFail();

        $this->assertSame($expectedPath, $post->featured_image_path);
        $this->assertFileExists($this->blogImagesRoot . DIRECTORY_SEPARATOR . 'local-preview-boundary-post.png');

        $afterSavePaths = collect(
            $this->actingAs($admin)->getJson(route('admin.media.feed', [
                'folder' => 'blog',
                'page' => 1,
                'per_page' => 24,
                'search' => '',
            ]))->json('media.data')
        )->pluck('path')->all();

        $this->assertContains($expectedPath, $afterSavePaths);
    }

    public function test_selecting_existing_saved_library_media_keeps_the_saved_path_without_creating_a_new_upload(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::factory()->create();
        $existingPath = '/images/blog/existing-saved-library.png';

        File::put($this->blogImagesRoot . DIRECTORY_SEPARATOR . 'existing-saved-library.png', 'saved-image');

        $feedPaths = collect(
            $this->actingAs($admin)->getJson(route('admin.media.feed', [
                'folder' => 'blog',
                'page' => 1,
                'per_page' => 24,
                'search' => '',
            ]))->json('media.data')
        )->pluck('path')->all();

        $this->assertContains($existingPath, $feedPaths);

        $this->actingAs($admin)
            ->post(route('admin.posts.store'), [
                'title' => 'Existing Saved Media Post',
                'content' => '<p>Body</p>',
                'category_id' => $category->id,
                'featured_image_path' => $existingPath,
            ])
            ->assertRedirect();

        $post = Post::query()->where('title', 'Existing Saved Media Post')->firstOrFail();

        $this->assertSame($existingPath, $post->featured_image_path);
        $this->assertFileExists($this->blogImagesRoot . DIRECTORY_SEPARATOR . 'existing-saved-library.png');
        $this->assertFileDoesNotExist($this->blogImagesRoot . DIRECTORY_SEPARATOR . 'existing-saved-media-post.png');
    }
}
