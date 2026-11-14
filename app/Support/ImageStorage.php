<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** The single place that knows where uploaded images live. */
final class ImageStorage
{
    /** Width of generated list thumbnails (px). Cards are at most ~480 CSS px wide. */
    public const THUMB_WIDTH = 480;

    public static function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
    }

    /**
     * Stores the image and a small WebP thumbnail next to it.
     *
     * @return array{0: string, 1: string|null} the image path and the thumbnail path (null when GD cannot make one)
     */
    public static function storeWithThumb(UploadedFile $file, string $directory): array
    {
        $path = self::store($file, $directory);

        return [$path, self::makeThumb($file->getRealPath() ?: '', $path)];
    }

    /** Creates `thumbs/<path>.webp` from an image file on disk; returns its path or null. */
    public static function makeThumb(string $sourceFile, string $path, int $width = self::THUMB_WIDTH): ?string
    {
        $bytes = $sourceFile === '' ? false : @file_get_contents($sourceFile);

        return $bytes === false ? null : self::makeThumbFromBytes($bytes, $path, $width);
    }

    /** Creates `thumbs/<path>.webp` from raw image bytes; returns its path or null when GD cannot. */
    public static function makeThumbFromBytes(string $bytes, string $path, int $width = self::THUMB_WIDTH): ?string
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            return null;
        }

        $scaled = imagesx($source) > $width ? imagescale($source, $width) : $source;
        if ($scaled === false) {
            return null;
        }

        ob_start();
        imagewebp($scaled, null, 80);
        $webp = (string) ob_get_clean();

        $thumb = 'thumbs/'.$path.'.webp';
        Storage::disk('public')->put($thumb, $webp);

        return $thumb;
    }

    /** Public URL of a stored image. Empty paths give null; absolute URLs (seed data, CDNs) pass through. */
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : asset('storage/'.$path);
    }

    /** URL of the list thumbnail, falling back to the full image for content that has none. */
    public static function thumbUrl(?string $thumb, ?string $image): ?string
    {
        return self::url($thumb ?: $image);
    }

    public static function delete(?string ...$paths): void
    {
        $paths = array_filter($paths);

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }
    }
}
