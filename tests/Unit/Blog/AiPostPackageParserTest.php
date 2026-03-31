<?php

namespace Tests\Unit\Blog;

use App\Services\Blog\AiPostPackageParser;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AiPostPackageParserTest extends TestCase
{
    public function test_it_parses_a_valid_package(): void
    {
        $parser = new AiPostPackageParser();

        $parsed = $parser->parse($this->validPackage());

        $this->assertSame('Buyer Mistakes: A Strategy Breakdown for Winning Smarter in Today\'s Market', $parsed['title']);
        $this->assertSame('buyer-mistakes-strategies', $parsed['slug']);
        $this->assertSame('Buyers', $parsed['category']);
        $this->assertSame(['buyer mistakes', 'home buying strategy', 'first-time home buyers'], $parsed['tags']);
        $this->assertFalse($parsed['noindex']);
        $this->assertSame([
            'title',
            'article',
            'seo_title',
            'slug',
            'excerpt',
            'sources',
            'category',
            'tags',
            'meta_title',
            'meta_description',
            'canonical_url',
            'og_title',
            'og_description',
            'featured_image_path',
            'og_image_path',
            'noindex',
        ], array_keys($parsed));
    }

    public function test_it_rejects_wrong_list_order(): void
    {
        $this->expectException(ValidationException::class);

        $parser = new AiPostPackageParser();
        $parser->parse(str_replace('- SEO Title:', '- Slug:', $this->validPackage()));
    }

    public function test_it_rejects_missing_required_list_rows(): void
    {
        $this->expectException(ValidationException::class);

        $parser = new AiPostPackageParser();
        $parser->parse(str_replace("- OG Image Path: /images/blog/buyer-mistakes-strategies-og.jpg\n", '', $this->validPackage()));
    }

    public function test_it_rejects_packages_that_do_not_follow_title_article_list_structure(): void
    {
        $this->expectException(ValidationException::class);

        $parser = new AiPostPackageParser();
        $parser->parse("TITLE:\nOnly a title\n\nLIST:\n- SEO Title: Missing article");
    }

    public function test_it_rejects_invalid_noindex_values(): void
    {
        $this->expectException(ValidationException::class);

        $parser = new AiPostPackageParser();
        $parser->parse(str_replace('- Noindex: No', '- Noindex: Maybe', $this->validPackage()));
    }

    public function test_it_rejects_unsafe_canonical_urls(): void
    {
        $this->expectException(ValidationException::class);

        $parser = new AiPostPackageParser();
        $parser->parse(str_replace(
            'https://www.example.com/blog/buyer-mistakes-strategies',
            'javascript:alert(1)',
            $this->validPackage()
        ));
    }

    private function validPackage(): string
    {
        return <<<'TEXT'
TITLE:
Buyer Mistakes: A Strategy Breakdown for Winning Smarter in Today's Market

ARTICLE:
<p>Buying a home is exciting for a reason.</p>
<p>It feels like progress, possibility, and a real move forward.</p>

LIST:
- SEO Title: Buyer Mistakes to Avoid: Smart Strategies for Today's Market
- Slug: buyer-mistakes-strategies
- Excerpt: Buyers have more room to negotiate in today's market, but that does not mean mistakes have disappeared.
- Sources: Freddie Mac PMMS; National Association of Realtors; Consumer Financial Protection Bureau
- Category: Buyers
- Tags: buyer mistakes, home buying strategy, first-time home buyers
- Meta Title: Buyer Mistakes to Avoid in 2026 | Smart Home Buying Strategies
- Meta Description: Learn the biggest buyer mistakes in today's housing market and how to avoid them.
- Canonical URL: https://www.example.com/blog/buyer-mistakes-strategies
- OG Title: Buyer Mistakes to Avoid: A Smart Strategy Breakdown
- OG Description: A clear, practical breakdown of common buyer mistakes.
- Featured Image Path: /images/blog/buyer-mistakes-strategies-cover.jpg
- OG Image Path: /images/blog/buyer-mistakes-strategies-og.jpg
- Noindex: No
TEXT;
    }
}
