<?php

namespace Tests\Regression\Media;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\Concerns\UsesIsolatedMediaRoot;
use Tests\TestCase;

class MediaPersistenceAfterFreshFetchTest extends TestCase
{
    use RefreshDatabase;
    use UsesIsolatedMediaRoot;

    private string $imagesRoot;

    private string $folder = 'test-persist-fresh-fetch';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpIsolatedMediaRoot();
        $this->imagesRoot = $this->isolatedImagesRoot();

        File::ensureDirectoryExists($this->imagesRoot);
        File::ensureDirectoryExists($this->imagesRoot . DIRECTORY_SEPARATOR . $this->folder);
    }

    public function test_uploaded_media_still_exists_in_the_feed_after_a_fresh_follow_up_request(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $uploadResponse = $this->actingAs($admin)
            ->post(route('admin.media.store'), [
                'folder' => $this->folder,
                'image' => UploadedFile::fake()->image('persist-across-fetches.png'),
            ]);

        $uploadedItem = $uploadResponse->json('item');

        $uploadResponse->assertOk()
            ->assertJsonPath('item.folder', $this->folder);

        $this->assertFileExists(
            $this->imagesRoot . DIRECTORY_SEPARATOR . $this->folder . DIRECTORY_SEPARATOR . $uploadedItem['filename']
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
            ->firstWhere('path', $uploadedItem['path']);

        $this->assertNotNull($item, 'Fresh media fetch no longer returned the uploaded image.');
        $this->assertSame($uploadedItem['path'], $item['path']);
        $this->assertSame($uploadedItem['url'], $item['url']);
        $this->assertSame($uploadedItem['filename'], $item['filename']);
    }
}
