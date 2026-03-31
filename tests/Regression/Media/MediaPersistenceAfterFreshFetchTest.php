<?php

namespace Tests\Regression\Media;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MediaPersistenceAfterFreshFetchTest extends TestCase
{
    use RefreshDatabase;

    private string $imagesRoot;

    private string $folder = 'test-persist-fresh-fetch';

    protected function setUp(): void
    {
        parent::setUp();

        $this->imagesRoot = public_path('images');

        File::ensureDirectoryExists($this->imagesRoot);
        File::ensureDirectoryExists($this->imagesRoot . DIRECTORY_SEPARATOR . $this->folder);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->imagesRoot . DIRECTORY_SEPARATOR . $this->folder);

        parent::tearDown();
    }

    public function test_uploaded_media_still_exists_in_the_feed_after_a_fresh_follow_up_request(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $uploadResponse = $this->actingAs($admin)
            ->post(route('admin.media.store'), [
                'folder' => $this->folder,
                'image' => UploadedFile::fake()->image('persist-across-fetches.png'),
            ]);

        $uploadResponse->assertOk()
            ->assertJsonPath('item.path', '/images/' . $this->folder . '/persist-across-fetches.png')
            ->assertJsonPath('item.url', '/images/' . $this->folder . '/persist-across-fetches.png');

        $this->assertFileExists(
            $this->imagesRoot . DIRECTORY_SEPARATOR . $this->folder . DIRECTORY_SEPARATOR . 'persist-across-fetches.png'
        );

        $freshResponse = $this->actingAs($admin)->getJson(route('admin.media.feed', [
            'folder' => $this->folder,
            'page' => 1,
            'per_page' => 24,
            'search' => '',
        ]));

        $freshResponse->assertOk()
            ->assertJsonPath('filters.folder', $this->folder);

        $item = collect($freshResponse->json('media.data'))
            ->firstWhere('path', '/images/' . $this->folder . '/persist-across-fetches.png');

        $this->assertNotNull($item, 'Fresh media fetch no longer returned the uploaded image.');
        $this->assertSame('/images/' . $this->folder . '/persist-across-fetches.png', $item['path']);
        $this->assertSame('/images/' . $this->folder . '/persist-across-fetches.png', $item['url']);
        $this->assertSame('persist-across-fetches.png', $item['filename']);
    }
}
