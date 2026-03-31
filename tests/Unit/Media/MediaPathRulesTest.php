<?php

namespace Tests\Unit\Media;

use App\Models\Post;
use Tests\TestCase;

class MediaPathRulesTest extends TestCase
{
    public function test_post_featured_image_url_normalizes_supported_path_shapes(): void
    {
        $this->assertSame(
            '/images/blog/cover.png',
            (new Post(['featured_image_path' => '/images/blog/cover.png']))->featured_image_url
        );

        $this->assertSame(
            '/images/blog/cover.png',
            (new Post(['featured_image_path' => 'images/blog/cover.png']))->featured_image_url
        );

        $this->assertSame(
            '/images/blog/cover.png',
            (new Post(['featured_image_path' => '/storage/images/blog/cover.png']))->featured_image_url
        );

        $this->assertSame(
            '/images/blog/cover.png',
            (new Post(['featured_image_path' => 'storage/images/blog/cover.png']))->featured_image_url
        );

        $this->assertSame(
            'https://cdn.example.com/cover.png',
            (new Post(['featured_image_path' => 'https://cdn.example.com/cover.png']))->featured_image_url
        );
    }

    public function test_post_featured_image_url_returns_null_when_no_path_exists(): void
    {
        $this->assertNull((new Post(['featured_image_path' => null]))->featured_image_url);
    }

    public function test_normalize_managed_image_path_returns_canonical_images_path(): void
    {
        $this->assertSame('/images/blog/cover.png', Post::normalizeManagedImagePath('/images/blog/cover.png'));
        $this->assertSame('/images/blog/cover.png', Post::normalizeManagedImagePath('images/blog/cover.png'));
        $this->assertSame('/images/blog/cover.png', Post::normalizeManagedImagePath('/storage/images/blog/cover.png'));
        $this->assertSame('/images/blog/cover.png', Post::normalizeManagedImagePath('storage/images/blog/cover.png'));
        $this->assertNull(Post::normalizeManagedImagePath('https://cdn.example.com/cover.png'));
    }
}
