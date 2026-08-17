<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MenuVariantImageService
{
    private const MAX_EDGE = 640;

    private const WEBP_QUALITY = 82;

    public function store(UploadedFile $image): string
    {
        $contents = @file_get_contents($image->getRealPath());
        $optimized = is_string($contents)
            ? $this->encodeWebpThumbnail($contents)
            : null;
        if ($optimized !== null) {
            $path = 'menu-variants/'.Str::uuid().'.webp';
            if (Storage::disk('public')->put($path, $optimized)) {
                return $path;
            }
        }

        $path = $image->store('menu-variants', 'public');
        if ($path === false) {
            throw ValidationException::withMessages([
                'image' => 'Gambar varian gagal disimpan. Silakan coba lagi.',
            ]);
        }

        return $path;
    }

    public function convertStoredToWebp(string $sourcePath): string
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($sourcePath)) {
            throw new RuntimeException("File gambar tidak ditemukan: {$sourcePath}");
        }

        $contents = $disk->get($sourcePath);
        $optimized = $this->encodeWebpThumbnail($contents);
        if ($optimized === null) {
            throw new RuntimeException("File gambar tidak dapat dikonversi: {$sourcePath}");
        }

        $directory = trim(str_replace('\\', '/', dirname($sourcePath)), './');
        $targetPath = ($directory !== '' ? $directory.'/' : '').Str::uuid().'.webp';
        if (! $disk->put($targetPath, $optimized)) {
            throw new RuntimeException("Gambar WebP gagal disimpan: {$sourcePath}");
        }

        return $targetPath;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function encodeWebpThumbnail(string $contents): ?string
    {
        if (
            ! function_exists('imagecreatefromstring') ||
            ! function_exists('imagecreatetruecolor') ||
            ! function_exists('imagecopyresampled') ||
            ! function_exists('imagewebp')
        ) {
            return null;
        }

        $source = @imagecreatefromstring($contents);
        if ($source === false) {
            return null;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        if ($sourceWidth < 1 || $sourceHeight < 1) {
            imagedestroy($source);

            return null;
        }

        $scale = min(1, self::MAX_EDGE / max($sourceWidth, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        ob_start();
        $encoded = imagewebp($target, null, self::WEBP_QUALITY);
        $webp = ob_get_clean();

        imagedestroy($target);
        imagedestroy($source);

        return $encoded && is_string($webp) && $webp !== '' ? $webp : null;
    }
}
