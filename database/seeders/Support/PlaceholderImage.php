<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Storage;

/** Generates simple gradient cover images with GD so demo data has pictures without any download. */
final class PlaceholderImage
{
    /**
     * Stores a PNG on the public disk and returns its path ('' when GD is not available).
     *
     * @param  array{0: string, 1: string}  $colors  two hex colors, e.g. ['#4c1d95', '#22d3ee']
     */
    public static function create(string $path, array $colors, int $width = 960, int $height = 600): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return '';
        }

        [$r1, $g1, $b1] = sscanf($colors[0], '#%02x%02x%02x');
        [$r2, $g2, $b2] = sscanf($colors[1], '#%02x%02x%02x');

        $image = imagecreatetruecolor($width, $height);

        // Diagonal-ish gradient: blend by row, tilted by shifting the blend with the column in 8 bands.
        for ($y = 0; $y < $height; $y++) {
            $t = $y / ($height - 1);
            $color = imagecolorallocate(
                $image,
                (int) ($r1 + ($r2 - $r1) * $t),
                (int) ($g1 + ($g2 - $g1) * $t),
                (int) ($b1 + ($b2 - $b1) * $t),
            );
            imageline($image, 0, $y, $width, $y, $color);
        }

        imagealphablending($image, true);

        // Soft translucent circles give the covers some depth.
        $circles = [[0.18, 0.25, 0.55, 22], [0.82, 0.78, 0.7, 16], [0.62, 0.2, 0.3, 28]];
        foreach ($circles as [$cx, $cy, $size, $alpha]) {
            $white = imagecolorallocatealpha($image, 255, 255, 255, 127 - $alpha);
            imagefilledellipse($image, (int) ($width * $cx), (int) ($height * $cy), (int) ($width * $size), (int) ($width * $size), $white);
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put($path, $png);

        return $path;
    }
}
