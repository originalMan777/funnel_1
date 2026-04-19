<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait UsesIsolatedMediaRoot
{
    protected string $isolatedMediaBasePath;

    protected string $isolatedImagesRoot;

    protected function setUpIsolatedMediaRoot(): void
    {
        $token = Str::slug(str_replace('\\', '-', static::class)) . '-' . Str::lower(Str::random(10));

        $this->isolatedMediaBasePath = storage_path('framework/testing/media/' . $token);
        $this->isolatedImagesRoot = $this->isolatedMediaBasePath . DIRECTORY_SEPARATOR . 'images';

        config()->set('media.images_root', $this->isolatedImagesRoot);

        File::ensureDirectoryExists($this->isolatedImagesRoot);
        File::ensureDirectoryExists($this->isolatedBlogImagesRoot());

        $this->beforeApplicationDestroyed(function (): void {
            File::deleteDirectory($this->isolatedMediaBasePath);
        });
    }

    protected function isolatedImagesRoot(): string
    {
        return $this->isolatedImagesRoot;
    }

    protected function isolatedBlogImagesRoot(): string
    {
        return $this->isolatedImagesRoot . DIRECTORY_SEPARATOR . 'blog';
    }

    protected function ensureIsolatedMediaFolder(string $folder): string
    {
        $path = $folder === '__root__'
            ? $this->isolatedImagesRoot()
            : $this->isolatedImagesRoot() . DIRECTORY_SEPARATOR . trim($folder, '/');

        File::ensureDirectoryExists($path);

        return $path;
    }

    protected function putTinyPng(string $path): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WnSUs8AAAAASUVORK5CYII=',
            true
        ));
    }
}
