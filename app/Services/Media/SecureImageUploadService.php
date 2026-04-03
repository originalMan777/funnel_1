<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SecureImageUploadService
{
    public function storeForBlogPost(UploadedFile $file, string $baseName): string
    {
        return $this->storeToImagesFolder($file, 'blog', $baseName);
    }

    public function storeForMediaLibrary(UploadedFile $file, string $folder, string $baseName): string
    {
        return $this->storeToImagesFolder($file, $folder, $baseName);
    }

    private function storeToImagesFolder(UploadedFile $file, string $folder, string $baseName): string
    {
        $realPath = $file->getRealPath();

        if ($realPath === false) {
            abort(422, 'Invalid uploaded image.');
        }

        $imageInfo = @getimagesize($realPath);

        if ($imageInfo === false || !isset($imageInfo['mime'])) {
            abort(422, 'Uploaded file is not a valid image.');
        }

        $mime = (string) $imageInfo['mime'];
        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);

        if ($width <= 0 || $height <= 0) {
            abort(422, 'Uploaded file is not a valid image.');
        }

        $imagesRoot = config('media.images_root', public_path('images'));

        if (!File::isDirectory($imagesRoot)) {
            File::makeDirectory($imagesRoot, 0755, true);
        }

        $directory = $folder === '__root__'
            ? $imagesRoot
            : $imagesRoot . DIRECTORY_SEPARATOR . $folder;

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $realImagesRoot = realpath($imagesRoot) ?: $imagesRoot;
        $realDirectory = realpath($directory) ?: $directory;

        if (!Str::startsWith($realDirectory, $realImagesRoot)) {
            abort(422, 'Invalid media folder.');
        }

        $maxWidth = 2560;
        $maxHeight = 2560;

        $targetWidth = $width;
        $targetHeight = $height;

        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $targetWidth = max(1, (int) round($width * $ratio));
            $targetHeight = max(1, (int) round($height * $ratio));
        }

        switch ($mime) {
            case 'image/jpeg':
                $source = @imagecreatefromjpeg($realPath);
                $extension = 'jpg';
                break;

            case 'image/png':
                $source = @imagecreatefrompng($realPath);
                $extension = 'png';
                break;

            case 'image/webp':
                if (!function_exists('imagecreatefromwebp')) {
                    abort(422, 'WEBP processing is not supported on this server.');
                }

                $source = @imagecreatefromwebp($realPath);
                $extension = 'webp';
                break;

            default:
                abort(422, 'Unsupported image type.');
        }

        if ($source === false) {
            abort(422, 'Unable to process uploaded image.');
        }

        $processed = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($processed === false) {
            imagedestroy($source);
            abort(500, 'Unable to process uploaded image.');
        }

        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($processed, false);
            imagesavealpha($processed, true);
            $transparent = imagecolorallocatealpha($processed, 0, 0, 0, 127);
            imagefilledrectangle($processed, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        imagecopyresampled(
            $processed,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height
        );

        $filenameBase = Str::slug($baseName);

        if ($filenameBase === '') {
            $filenameBase = 'image';
        }

        $candidate = $filenameBase . '.' . $extension;
        $counter = 2;

        while (File::exists($directory . DIRECTORY_SEPARATOR . $candidate)) {
            $candidate = $filenameBase . '-' . $counter . '.' . $extension;
            $counter++;
        }

        $destination = $directory . DIRECTORY_SEPARATOR . $candidate;

        $saved = match ($mime) {
            'image/jpeg' => imagejpeg($processed, $destination, 85),
            'image/png' => imagepng($processed, $destination),
            'image/webp' => imagewebp($processed, $destination, 85),
            default => false,
        };

        imagedestroy($source);
        imagedestroy($processed);

        if ($saved === false) {
            abort(500, 'Unable to save processed image.');
        }

        return $folder === '__root__'
            ? '/images/' . $candidate
            : '/images/' . $folder . '/' . $candidate;
    }
}
