<?php

namespace App\Services;

/**
 * Automatic image optimization for uploads (GD):
 * - fixes phone-camera rotation (EXIF), then strips all metadata (incl. GPS location)
 * - resizes large images down to a maximum dimension (never upscales)
 * - encodes to WebP when supported (keeps transparency), otherwise JPEG / PNG
 * - re-encoding also neutralises files that only pretend to be images
 *
 * If GD is missing or the image is too large for the available memory, it returns null
 * and the caller keeps the original file — uploads never fail because of optimization.
 */
class ImageOptimizer
{
    public function available(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatetruecolor');
    }

    public function supportsWebp(): bool
    {
        return $this->available() && function_exists('imagewebp') && (imagetypes() & IMG_WEBP);
    }

    /**
     * @param  array<string, int>  $sizes  variant name => max width/height, e.g. ['main' => 1600, 'thumb' => 600]
     * @return array<string, array{contents: string, extension: string, mime: string}>|null
     */
    public function variants(string $path, array $sizes, int $quality = 82): ?array
    {
        if (!$this->available() || !($info = @getimagesize($path))) {
            return null;
        }

        [$width, $height, $type] = $info;
        if (!$this->fitsInMemory($width, $height)) {
            return null;
        }

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
        if (!$image) {
            return null;
        }

        if ($type === IMAGETYPE_JPEG) {
            $image = $this->applyExifOrientation($image, $path);
        }

        $result = [];
        foreach ($sizes as $name => $max) {
            $resized = $this->resize($image, $max);
            $result[$name] = $this->encode($resized, $type, $quality);
            if ($resized !== $image) imagedestroy($resized);
        }
        imagedestroy($image);

        return $result;
    }

    private function resize(\GdImage $image, int $max): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        if ($w <= $max && $h <= $max) {
            return $image;
        }

        $ratio = min($max / $w, $max / $h);
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));

        $canvas = imagecreatetruecolor($nw, $nh);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $canvas;
    }

    /** @return array{contents: string, extension: string, mime: string} */
    private function encode(\GdImage $image, int $sourceType, int $quality): array
    {
        ob_start();
        if ($this->supportsWebp()) {
            imagesavealpha($image, true);
            imagewebp($image, null, $quality);
            [$ext, $mime] = ['webp', 'image/webp'];
        } elseif ($sourceType === IMAGETYPE_PNG) {
            imagesavealpha($image, true);
            imagepng($image, null, 9);
            [$ext, $mime] = ['png', 'image/png'];
        } else {
            imageinterlace($image, true); // progressive JPEG
            imagejpeg($image, null, $quality);
            [$ext, $mime] = ['jpg', 'image/jpeg'];
        }

        return ['contents' => (string) ob_get_clean(), 'extension' => $ext, 'mime' => $mime];
    }

    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };
        if ($rotated) {
            imagedestroy($image);
            return $rotated;
        }

        return $image;
    }

    /** GD needs ~5 bytes per pixel (+ copies while resizing). */
    private function fitsInMemory(int $width, int $height): bool
    {
        $limit = $this->bytes((string) ini_get('memory_limit'));
        if ($limit <= 0) {
            return true;
        }

        return ($width * $height * 5 * 2) + (16 * 1024 * 1024) < ($limit - memory_get_usage());
    }

    private function bytes(string $value): int
    {
        $value = trim($value);
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
