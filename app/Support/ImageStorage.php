<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** The single place that knows where uploaded images live. */
final class ImageStorage
{
    public static function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
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

    public static function delete(?string ...$paths): void
    {
        $paths = array_filter($paths);

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }
    }
}
