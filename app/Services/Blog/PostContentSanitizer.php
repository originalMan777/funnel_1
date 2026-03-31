<?php

namespace App\Services\Blog;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostContentSanitizer
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $allowedTags = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'blockquote' => [],
        'a' => ['href', 'title'],
    ];

    public function sanitizeForStorage(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            throw ValidationException::withMessages([
                'content' => 'Post content cannot be empty.',
            ]);
        }

        if (mb_strlen($html) > 200000) {
            throw ValidationException::withMessages([
                'content' => 'Post content is too large.',
            ]);
        }

        return $this->sanitizeHtml($html);
    }

    public function normalizeAndSanitizeArticle(string $article): string
    {
        $article = trim($article);

        if ($article === '') {
            throw ValidationException::withMessages([
                'article' => 'The ARTICLE section cannot be empty.',
            ]);
        }

        if (mb_strlen($article) > 200000) {
            throw ValidationException::withMessages([
                'article' => 'The ARTICLE section is too large.',
            ]);
        }

        if (!preg_match('/<\s*[a-z][^>]*>/i', $article)) {
            $paragraphs = preg_split('/\n{2,}/', $article) ?: [];

            $htmlParagraphs = array_map(function (string $paragraph): string {
                $paragraph = trim($paragraph);
                $paragraph = htmlspecialchars($paragraph, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $paragraph = nl2br($paragraph, false);

                return '<p>' . $paragraph . '</p>';
            }, array_filter($paragraphs, static fn (string $paragraph): bool => trim($paragraph) !== ''));

            $article = implode("\n", $htmlParagraphs);
        }

        return $this->sanitizeHtml($article);
    }

    private function sanitizeHtml(string $html): string
    {
        $html = $this->preSanitize($html);

        libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrappedHtml = '<!DOCTYPE html><html><body><div id="content-root">' . $html . '</div></body></html>';

        $dom->loadHTML(
            mb_convert_encoding($wrappedHtml, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        $root = $this->findContentRoot($dom);

        if (!$root instanceof DOMElement) {
            return '';
        }

        $this->sanitizeNodeTree($root);

        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    private function preSanitize(string $html): string
    {
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? $html;
        $html = preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $html) ?? $html;
        $html = preg_replace('#<object\b[^>]*>.*?</object>#is', '', $html) ?? $html;
        $html = preg_replace('#<embed\b[^>]*>.*?</embed>#is', '', $html) ?? $html;
        $html = preg_replace('#<embed\b[^>]*\/?>#is', '', $html) ?? $html;
        $html = preg_replace('#<form\b[^>]*>.*?</form>#is', '', $html) ?? $html;
        $html = preg_replace('#<svg\b[^>]*>.*?</svg>#is', '', $html) ?? $html;
        $html = preg_replace('/[^\P{C}\n\t]+/u', '', $html) ?? $html;

        return trim($html);
    }

    private function findContentRoot(DOMDocument $dom): ?DOMElement
    {
        foreach ($dom->getElementsByTagName('div') as $div) {
            if ($div instanceof DOMElement && $div->getAttribute('id') === 'content-root') {
                return $div;
            }
        }

        return null;
    }

    private function sanitizeNodeTree(DOMNode $node): void
    {
        $children = [];

        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof DOMElement) {
                $tag = Str::lower($child->tagName);

                if (!array_key_exists($tag, $this->allowedTags)) {
                    $this->unwrapElement($child);
                    continue;
                }

                $this->sanitizeAttributes($child, $tag);
                $this->sanitizeNodeTree($child);
                continue;
            }

            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
            }
        }
    }

    private function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowedAttributes = $this->allowedTags[$tag] ?? [];

        $attributes = [];

        foreach ($element->attributes as $attribute) {
            $attributes[] = $attribute->nodeName;
        }

        foreach ($attributes as $attributeName) {
            $lowerName = Str::lower($attributeName);

            if (!in_array($lowerName, $allowedAttributes, true)) {
                $element->removeAttribute($attributeName);
                continue;
            }

            $value = trim((string) $element->getAttribute($attributeName));

            if ($lowerName === 'href') {
                $safeHref = $this->sanitizeHref($value);

                if ($safeHref === null) {
                    $element->removeAttribute($attributeName);
                } else {
                    $element->setAttribute('href', $safeHref);
                }
            }

            if ($lowerName === 'title') {
                $element->setAttribute(
                    'title',
                    trim(strip_tags($value))
                );
            }
        }

        if ($tag === 'a' && $element->hasAttribute('href')) {
            $element->setAttribute('rel', 'nofollow noopener noreferrer');
            $element->setAttribute('target', '_blank');
        }
    }

    private function sanitizeHref(string $href): ?string
    {
        if ($href === '') {
            return null;
        }

        $lower = Str::lower($href);

        if (Str::startsWith($lower, ['javascript:', 'data:', 'vbscript:', 'file:'])) {
            return null;
        }

        if (preg_match('/^\s*#/u', $href)) {
            return '#';
        }

        if (preg_match('/^\s*https?:\/\//iu', $href)) {
            return $href;
        }

        if (preg_match('/^\s*\/(?!\/)/u', $href)) {
            return $href;
        }

        return null;
    }

    private function unwrapElement(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if (!$parent instanceof DOMNode) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
