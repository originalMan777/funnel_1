<?php

namespace Tests\Integration\Blog;

use App\Services\Blog\PostContentSanitizer;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PostPersistenceSanitizationTest extends TestCase
{
    public function test_storage_sanitizer_strips_active_content_and_keeps_allowed_markup(): void
    {
        $clean = app(PostContentSanitizer::class)->sanitizeForStorage(
            '<p>Hello</p><script>alert(1)</script><iframe src="https://evil.example"></iframe><a href="javascript:alert(1)">Click</a><a href="https://example.com" title="<b>X</b>">Safe</a>'
        );

        $this->assertStringContainsString('<p>Hello</p>', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('<iframe', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('href="https://example.com"', $clean);
        $this->assertStringContainsString('rel="nofollow noopener noreferrer"', $clean);
    }

    public function test_storage_sanitizer_rejects_empty_content(): void
    {
        $this->expectException(ValidationException::class);

        app(PostContentSanitizer::class)->sanitizeForStorage('   ');
    }
}
