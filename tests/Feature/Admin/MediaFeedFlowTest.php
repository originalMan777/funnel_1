<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MediaFeedFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $imagesRoot;

    private string $customFolder = 'test-media-feed';

    protected function setUp(): void
    {
        parent::setUp();

        $this->imagesRoot = public_path('images');

        File::ensureDirectoryExists($this->imagesRoot);
        File::ensureDirectoryExists($this->imagesRoot . DIRECTORY_SEPARATOR . 'blog');
        File::ensureDirectoryExists($this->imagesRoot . DIRECTORY_SEPARATOR . $this->customFolder);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->imagesRoot . DIRECTORY_SEPARATOR . $this->customFolder);
        File::delete($this->imagesRoot . DIRECTORY_SEPARATOR . 'feed-root-proof.png');
        File::delete($this->imagesRoot . DIRECTORY_SEPARATOR . 'blog' . DIRECTORY_SEPARATOR . 'feed-blog-proof.png');
        File::delete($this->imagesRoot . DIRECTORY_SEPARATOR . 'blog' . DIRECTORY_SEPARATOR . 'fresh-feed-proof.png');

        parent::tearDown();
    }

    public function test_guests_are_redirected_and_non_admin_users_are_forbidden_from_media_feed(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->get(route('admin.media.feed'))
            ->assertRedirect(route('login'));

        $this->actingAs($user)
            ->get(route('admin.media.feed'))
            ->assertForbidden();
    }

    public function test_verified_admin_can_upload_media_and_fetch_it_from_the_feed_on_a_fresh_request(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $uploadResponse = $this->actingAs($admin)
    ->postJson(route('admin.media.store'), [
        'folder' => 'blog',
        'image' => UploadedFile::fake()->image('fresh-feed-proof.png'),
    ]);

            $uploadResponse->assertOk();

            $uploadedPath = $uploadResponse->json('item.path');
            $uploadedUrl = $uploadResponse->json('item.url');

            $this->assertStringStartsWith('/images/blog/image-', $uploadedPath);
            $this->assertStringEndsWith('.png', $uploadedPath);
            $this->assertSame($uploadedPath, $uploadedUrl);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.media.feed', [
                'folder' => 'blog',
                'page' => 1,
                'per_page' => 24,
                'search' => '',
            ]));

        $response->assertOk()
            ->assertJsonPath('filters.folder', 'blog');

        $item = collect($response->json('media.data'))
    ->firstWhere('path', $uploadedPath);

        $this->assertNotNull($item, 'Fresh media feed omitted a valid uploaded image.');
        $this->assertSame($uploadedPath, $item['path']);
        $this->assertSame($uploadedPath, $item['url']);
        $this->assertSame('blog', $item['folder']);

        $uploadedFilename = basename($uploadedPath);

        $this->assertStringStartsWith('image-', $uploadedFilename);
        $this->assertStringEndsWith('.png', $uploadedFilename);

        $this->assertFileExists(
            $this->imagesRoot . DIRECTORY_SEPARATOR . 'blog' . DIRECTORY_SEPARATOR . $uploadedFilename
        );
   }

    public function test_media_feed_folder_filtering_returns_only_media_from_the_requested_folder(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

       $blogUpload = $this->actingAs($admin)->postJson(route('admin.media.store'), [
    'folder' => 'blog',
    'image' => UploadedFile::fake()->image('feed-blog-proof.png'),
        ]);

        $blogUpload->assertOk();
        $blogPath = $blogUpload->json('item.path');

        $rootUpload = $this->actingAs($admin)->postJson(route('admin.media.store'), [
            'folder' => '__root__',
            'image' => UploadedFile::fake()->image('feed-root-proof.png'),
        ]);

        $rootUpload->assertOk();
        $rootPath = $rootUpload->json('item.path');

        $blogResponse = $this->actingAs($admin)->getJson(route('admin.media.feed', [
            'folder' => 'blog',
            'page' => 1,
            'per_page' => 24,
            'search' => '',
        ]));

        $blogPaths = collect($blogResponse->json('media.data'))->pluck('path')->all();

        $this->assertContains($blogPath, $blogPaths);
        $this->assertNotContains($rootPath, $blogPaths);

        $rootResponse = $this->actingAs($admin)->getJson(route('admin.media.feed', [
            'folder' => '__root__',
            'page' => 1,
            'per_page' => 24,
            'search' => '',
        ]));

        $rootPaths = collect($rootResponse->json('media.data'))->pluck('path')->all();

        $this->assertContains($rootPath, $rootPaths);
        $this->assertNotContains($blogPath, $rootPaths);
    }
}
