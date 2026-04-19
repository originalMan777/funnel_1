<?php

namespace Tests\Contracts\Json;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\UsesIsolatedMediaRoot;
use Tests\TestCase;

class MediaFeedContractTest extends TestCase
{
    use RefreshDatabase;
    use UsesIsolatedMediaRoot;

    private string $imagesRoot;

    private string $folder = 'contract-media-feed';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpIsolatedMediaRoot();
        $this->imagesRoot = $this->isolatedImagesRoot();

        File::ensureDirectoryExists($this->imagesRoot);
        File::ensureDirectoryExists($this->imagesRoot . DIRECTORY_SEPARATOR . $this->folder);

        $this->putTinyPng($this->imagesRoot . DIRECTORY_SEPARATOR . $this->folder . DIRECTORY_SEPARATOR . 'contract-first.png');
        $this->putTinyPng($this->imagesRoot . DIRECTORY_SEPARATOR . $this->folder . DIRECTORY_SEPARATOR . 'contract-second.png');
    }

    public function test_media_feed_json_contract_matches_current_consumer_shape(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->getJson(route('admin.media.feed', [
            'folder' => $this->folder,
            'page' => 1,
            'per_page' => 1,
            'search' => 'contract',
        ]));

        $response->assertOk()->assertJsonStructure([
            'folders' => [
                '*' => ['value', 'label'],
            ],
            'filters' => ['folder', 'search', 'per_page'],
            'media' => [
                'current_page',
                'data' => [
                    '*' => [
                        'name',
                        'filename',
                        'folder',
                        'path',
                        'url',
                        'size_kb',
                        'modified_at',
                        'extension',
                    ],
                ],
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links' => [
                    '*' => ['url', 'label', 'active'],
                ],
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ],
        ]);

        $payload = $response->json();

        $this->assertSame(['folders', 'filters', 'media'], array_keys($payload));
        $this->assertSame($this->folder, $payload['filters']['folder']);
        $this->assertSame('contract', $payload['filters']['search']);
        $this->assertSame(1, $payload['filters']['per_page']);
        $this->assertContains('__root__', collect($payload['folders'])->pluck('value')->all());
        $this->assertContains('blog', collect($payload['folders'])->pluck('value')->all());
        $this->assertContains($this->folder, collect($payload['folders'])->pluck('value')->all());

        $this->assertSame(1, $payload['media']['current_page']);
        $this->assertSame(2, $payload['media']['last_page']);
        $this->assertSame(2, $payload['media']['total']);
        $this->assertSame(1, $payload['media']['per_page']);
        $this->assertCount(1, $payload['media']['data']);

        $item = $payload['media']['data'][0];

        $this->assertSame($this->folder, $item['folder']);
        $this->assertStringStartsWith('/images/' . $this->folder . '/', $item['path']);
        $this->assertSame($item['path'], $item['url']);
        $this->assertSame('png', $item['extension']);
        $this->assertNotEmpty($payload['media']['links']);
    }
}
