<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MediaLibraryUploadTest extends TestCase
{
    use RefreshDatabase;

    private string $imagesRoot;

    private string $allowedFolder = 'test-library';

    private string $escapeFolder = 'media-library-escape-target';

    protected function setUp(): void
    {
        parent::setUp();

        $this->imagesRoot = public_path('images');

        File::ensureDirectoryExists($this->imagesRoot);
        File::ensureDirectoryExists($this->imagesRoot . DIRECTORY_SEPARATOR . $this->allowedFolder);
        File::deleteDirectory(public_path($this->escapeFolder));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->imagesRoot . DIRECTORY_SEPARATOR . $this->allowedFolder);
        File::deleteDirectory(public_path($this->escapeFolder));

        parent::tearDown();
    }

    public function test_verified_admin_can_upload_to_an_allowed_media_folder(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.media.store'), [
            'folder' => $this->allowedFolder,
            'image' => UploadedFile::fake()->image('proof.png'),
        ]);

        $response->assertOk()
            ->assertJsonPath('item.folder', $this->allowedFolder)
            ->assertJsonPath('item.path', "/images/{$this->allowedFolder}/proof.png");

        $this->assertFileExists($this->imagesRoot . DIRECTORY_SEPARATOR . $this->allowedFolder . DIRECTORY_SEPARATOR . 'proof.png');
    }

    public function test_traversal_style_folder_input_is_rejected_and_does_not_write_outside_media_root(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.media.store'), [
            'folder' => "../{$this->escapeFolder}",
            'image' => UploadedFile::fake()->image('proof.png'),
        ]);

        $response->assertSessionHasErrors('folder');

        $this->assertDirectoryDoesNotExist(public_path($this->escapeFolder));
        $this->assertFileDoesNotExist(public_path($this->escapeFolder . DIRECTORY_SEPARATOR . 'proof.png'));
    }

    public function test_non_image_upload_is_rejected_and_not_persisted(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.media.store'), [
            'folder' => $this->allowedFolder,
            'image' => UploadedFile::fake()->createWithContent('not-an-image.txt', 'plain text payload'),
        ]);

        $response->assertSessionHasErrors('image');

        $this->assertFileDoesNotExist($this->imagesRoot . DIRECTORY_SEPARATOR . $this->allowedFolder . DIRECTORY_SEPARATOR . 'not-an-image.txt');
    }
}
