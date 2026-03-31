<?php

namespace App\Services\Blog;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiPostPackageParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $package): array
    {
        /*
        |--------------------------------------------------------------------------
        | 🔒 LAYER 1: HARD SIZE LIMIT
        |--------------------------------------------------------------------------
        */
        if (mb_strlen($package) > 250_000) {
            throw ValidationException::withMessages([
                'package' => 'Package too large.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 🔒 LAYER 2: NORMALIZATION (STRICT)
        |--------------------------------------------------------------------------
        */
        $normalized = $this->normalize($package);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'package' => 'Paste a complete post package before importing.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 🔒 LAYER 3: STRICT STRUCTURE LOCK
        |--------------------------------------------------------------------------
        */
        if (!preg_match('/\ATITLE:\n(.*?)\nARTICLE:\n(.*?)\nLIST:\n(.*)\z/s', $normalized, $matches)) {
            throw ValidationException::withMessages([
                'package' => 'Invalid structure. Must follow exact TITLE → ARTICLE → LIST format.',
            ]);
        }

        $title = trim($matches[1]);
        $article = trim($matches[2]);
        $list = trim($matches[3]);

        /*
        |--------------------------------------------------------------------------
        | 🔒 LAYER 4: FIELD VALIDATION
        |--------------------------------------------------------------------------
        */
        $this->validateTitle($title);
        $this->validateArticle($article);
        $this->validateListRaw($list);

        /*
        |--------------------------------------------------------------------------
        | 🔒 LAYER 5: STRICT LABEL PARSING
        |--------------------------------------------------------------------------
        */
        $parsed = $this->parseList($list);

        /*
        |--------------------------------------------------------------------------
        | 🔒 LAYER 6: FINAL VALIDATION
        |--------------------------------------------------------------------------
        */
        $this->validateFinal($parsed);

        return [
            'title' => $title,
            'article' => $article,
            'seo_title' => $parsed['SEO Title'],
            'slug' => $parsed['Slug'],
            'excerpt' => $parsed['Excerpt'],
            'sources' => $parsed['Sources'],
            'category' => $parsed['Category'],
            'tags' => $parsed['Tags'],
            'meta_title' => $parsed['Meta Title'],
            'meta_description' => $parsed['Meta Description'],
            'canonical_url' => $parsed['Canonical URL'],
            'og_title' => $parsed['OG Title'],
            'og_description' => $parsed['OG Description'],
            'featured_image_path' => $parsed['Featured Image Path'],
            'og_image_path' => $parsed['OG Image Path'],
            'noindex' => $parsed['Noindex'] === 'Yes',
        ];
    }

    private function normalize(string $input): string
    {
        $input = str_replace(["\r\n", "\r"], "\n", trim($input));
        $input = preg_replace('/^\xEF\xBB\xBF/', '', $input) ?? $input;

        // remove control chars
        $input = preg_replace('/[^\P{C}\n]+/u', '', $input) ?? $input;

        // collapse excessive spacing
        $input = preg_replace("/\n{3,}/", "\n\n", $input) ?? $input;

        return trim($input);
    }

    private function validateTitle(string $title): void
    {
        if ($title === '' || mb_strlen($title) > 255) {
            throw ValidationException::withMessages([
                'package' => 'Invalid TITLE.',
            ]);
        }

        if (preg_match('/[\x00-\x1F\x7F]/u', $title)) {
            throw ValidationException::withMessages([
                'package' => 'Invalid characters in TITLE.',
            ]);
        }
    }

    private function validateArticle(string $article): void
    {
        if ($article === '' || mb_strlen($article) < 40 || mb_strlen($article) > 200000) {
            throw ValidationException::withMessages([
                'package' => 'Invalid ARTICLE length.',
            ]);
        }

        // detect suspicious payload density
        if (substr_count(Str::lower($article), '<script') > 0) {
            throw ValidationException::withMessages([
                'package' => 'Suspicious content detected in ARTICLE.',
            ]);
        }
    }

    private function validateListRaw(string $list): void
    {
        if ($list === '' || mb_strlen($list) > 5000) {
            throw ValidationException::withMessages([
                'package' => 'Invalid LIST section.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parseList(string $list): array
    {
        $expectedLabels = [
            'SEO Title',
            'Slug',
            'Excerpt',
            'Sources',
            'Category',
            'Tags',
            'Meta Title',
            'Meta Description',
            'Canonical URL',
            'OG Title',
            'OG Description',
            'Featured Image Path',
            'OG Image Path',
            'Noindex',
        ];

        $lines = preg_split('/\n+/', $list) ?: [];

        if (count($lines) !== count($expectedLabels)) {
            throw ValidationException::withMessages([
                'package' => 'LIST must contain exactly ' . count($expectedLabels) . ' fields.',
            ]);
        }

        $output = [];

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if (!preg_match('/^(?:[-*•]\s+)?([^:]+):\s*(.*)$/u', $line, $m)) {
                throw ValidationException::withMessages([
                    'package' => 'Invalid LIST format.',
                ]);
            }

            $label = trim($m[1]);
            $value = trim($m[2]);

            if ($label !== $expectedLabels[$index]) {
                throw ValidationException::withMessages([
                    'package' => 'Invalid label order or spoofing detected.',
                ]);
            }

            $value = $this->sanitizeValue($value, $label);

            $output[$label] = $value;
        }

        return $output;
    }

    private function sanitizeValue(string $value, string $label): mixed
    {
        if (mb_strlen($value) > 2000) {
            throw ValidationException::withMessages([
                'package' => "Value too long for {$label}.",
            ]);
        }

        // kill inline attack vectors
        $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value) ?? $value;
        $value = preg_replace('/javascript:/i', '', $value) ?? $value;

        if ($label === 'Tags') {
            $tags = array_values(array_filter(array_map(
                fn ($t) => trim(strip_tags($t)),
                explode(',', $value)
            )));

            if ($tags === [] || count($tags) > 12) {
                throw ValidationException::withMessages([
                    'package' => 'Invalid tags.',
                ]);
            }

            return $tags;
        }

        if ($label === 'Noindex') {
            if (!in_array($value, ['Yes', 'No'], true)) {
                throw ValidationException::withMessages([
                    'package' => 'Noindex must be Yes or No.',
                ]);
            }

            return $value;
        }

        if (Str::contains(Str::lower($label), 'url')) {
            if (!$this->isSafeUrl($value)) {
                throw ValidationException::withMessages([
                    'package' => "Invalid URL in {$label}.",
                ]);
            }
        }

        return trim(strip_tags($value));
    }

    private function isSafeUrl(string $url): bool
    {
        if ($url === '') {
            return true;
        }

        $lower = Str::lower($url);

        if (Str::startsWith($lower, ['javascript:', 'data:', 'file:', 'vbscript:'])) {
            return false;
        }

        if (preg_match('/^https?:\/\//i', $url)) {
            return true;
        }

        if (preg_match('/^\/(?!\/)/', $url)) {
            return true;
        }

        return false;
    }

    private function validateFinal(array $data): void
    {
        if (empty($data['Slug']) || !preg_match('/^[a-z0-9\-]+$/', $data['Slug'])) {
            throw ValidationException::withMessages([
                'package' => 'Invalid slug.',
            ]);
        }
    }
}
