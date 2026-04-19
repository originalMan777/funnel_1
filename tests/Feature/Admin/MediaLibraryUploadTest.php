<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\Concerns\UsesIsolatedMediaRoot;
use Tests\TestCase;

class MediaLibraryUploadTest extends TestCase
{
    use RefreshDatabase;
    use UsesIsolatedMediaRoot;

    private string $imagesRoot;

    private string $allowedFolder = 'test-library';

    private string $escapeFolder = 'media-library-escape-target';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpIsolatedMediaRoot();
        $this->imagesRoot = $this->isolatedImagesRoot();

        File::ensureDirectoryExists($this->imagesRoot);
        File::ensureDirectoryExists($this->imagesRoot . DIRECTORY_SEPARATOR . $this->allowedFolder);
    }

    public function test_verified_admin_can_upload_to_an_allowed_media_folder(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.media.store'), [
            'folder' => $this->allowedFolder,
            'image' => UploadedFile::fake()->image('proof.png'),
        ]);

        $response->assertOk()
            ->assertJsonPath('item.folder', $this->allowedFolder);

            $path = $response->json('item.path');

            $this->assertStringStartsWith("/images/{$this->allowedFolder}/image-", $path);
            $this->assertStringEndsWith('.png', $path);


       $filename = basename($path);

            $this->assertFileExists(
                $this->imagesRoot . DIRECTORY_SEPARATOR . $this->allowedFolder . DIRECTORY_SEPARATOR . $filename
            );
    }

    public function test_traversal_style_folder_input_is_rejected_and_does_not_write_outside_media_root(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

       $response = $this->actingAs($admin)->postJson(route('admin.media.store'), [
            'folder' => "../{$this->escapeFolder}",
            'image' => UploadedFile::fake()->image('proof.png'),
        ]);

       $response->assertStatus(422)
    ->assertJsonValidationErrors('folder');

        $escapePath = dirname($this->imagesRoot) . DIRECTORY_SEPARATOR . $this->escapeFolder;

        $this->assertDirectoryDoesNotExist($escapePath);
        $this->assertFileDoesNotExist($escapePath . DIRECTORY_SEPARATOR . 'proof.png');
    }

    public function test_non_image_upload_is_rejected_and_not_persisted(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.media.store'), [
            'folder' => $this->allowedFolder,
            'image' => UploadedFile::fake()->createWithContent('not-an-image.txt', 'plain text payload'),
        ]);

        $response->assertStatus(422)
    ->assertJsonValidationErrors('image');

        $this->assertFileDoesNotExist($this->imagesRoot . DIRECTORY_SEPARATOR . $this->allowedFolder . DIRECTORY_SEPARATOR . 'not-an-image.txt');
    }
}
