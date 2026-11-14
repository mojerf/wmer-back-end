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

    public static function delete(?string ...$paths): void
    {
        $paths = array_filter($paths);

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }
    }
}
