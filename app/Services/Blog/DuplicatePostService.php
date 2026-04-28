<?php

namespace App\Services\Blog;

use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicatePostService
{
    public function duplicate(Post $post, int $userId): Post
    {
        return DB::transaction(function () use ($post, $userId): Post {
            $post->loadMissing('tags:id');

            $duplicate = $post->replicate([
                'slug',
                'status',
                'published_at',
                'archived_at',
                'is_featured',
                'canonical_url',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
            ]);

            $duplicate->forceFill([
                'title' => $this->copyTitle((string) $post->title),
                'slug' => $this->uniqueSlug((string) ($post->slug ?: $post->title)),
                'status' => Post::STATUS_DRAFT,
                'published_at' => null,
                'archived_at' => null,
                'is_featured' => false,
                'canonical_url' => null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ])->save();

            $duplicate->tags()->sync($post->tags->pluck('id')->all());

            return $duplicate->refresh();
        });
    }

    private function copyTitle(string $title): string
    {
        $baseTitle = trim($title) !== '' ? trim($title) : 'Untitled Post';
        $copyTitle = $baseTitle . ' (Copy)';

        if (mb_strlen($copyTitle) <= 255) {
            return $copyTitle;
        }

        return rtrim(mb_substr($baseTitle, 0, 248)) . ' (Copy)';
    }

    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            $base = 'post';
        }

        $copyBase = Str::limit($base, 240, '') . '-copy';

        $existing = Post::query()
            ->where('slug', 'like', $copyBase . '%')
            ->pluck('slug');

        if (! $existing->contains($copyBase)) {
            return $copyBase;
        }

        $counter = 2;

        do {
            $candidate = $copyBase . '-' . $counter;
            $counter++;
        } while ($existing->contains($candidate));

        return $candidate;
    }
}
