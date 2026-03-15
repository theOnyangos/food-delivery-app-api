<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PrivateAssetUploadService
{
    private const IMAGE_MAX_SIZE = 1920;

    /**
     * @return array{id: string, url: string, path: string}
     */
    public function upload(User $owner, UploadedFile $file, string $category = 'general'): array
    {
        $isImage = str_starts_with((string) $file->getMimeType(), 'image/');

        if ($isImage) {
            return $this->uploadImage($owner, $file, $category);
        }

        return $this->uploadGenericFile($owner, $file, $category);
    }

    /**
     * @return array{id: string, url: string, path: string}
     */
    private function uploadImage(User $owner, UploadedFile $file, string $category): array
    {
        $extension = $file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'jpg');
        $baseName = (string) Str::uuid();
        $path = "uploads/{$category}/{$baseName}.{$extension}";
        $fullPath = storage_path('app/private/'.$path);

        $this->ensureDirectoryFor($path);

        if (class_exists(\Intervention\Image\Laravel\Facades\Image::class)) {
            $image = \Intervention\Image\Laravel\Facades\Image::read($file);
            $image = $this->cropAndScale($image);
            $this->applyWatermarkVisible($image);
            $image->save($fullPath);
        } else {
            $file->storeAs(dirname($path), basename($path), 'local');
        }

        $media = $this->createMedia($owner, $path, $file->getClientOriginalName(), $file->getMimeType(), $category);

        $url = $this->normalizeUrl(URL::temporarySignedRoute(
            'uploads.serve',
            now()->addYears(5),
            ['media' => $media->id],
            absolute: true
        ));

        return [
            'id' => $media->id,
            'url' => $url,
            'path' => $path,
        ];
    }

    /**
     * @return array{id: string, url: string, path: string}
     */
    private function uploadGenericFile(User $owner, UploadedFile $file, string $category): array
    {
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?? 'bin';
        $baseName = (string) Str::uuid();
        $path = "uploads/{$category}/{$baseName}.{$extension}";

        $this->ensureDirectoryFor($path);
        $file->storeAs(dirname($path), basename($path), 'local');

        $media = $this->createMedia($owner, $path, $file->getClientOriginalName(), $file->getMimeType(), $category);

        $url = $this->normalizeUrl(URL::temporarySignedRoute(
            'uploads.serve',
            now()->addYears(5),
            ['media' => $media->id],
            absolute: true
        ));

        return [
            'id' => $media->id,
            'url' => $url,
            'path' => $path,
        ];
    }

    private function normalizeUrl(string $url): string
    {
        return (string) preg_replace('#(?<!:)//+#', '/', $url);
    }

    private function cropAndScale(object $image): object
    {
        $w = method_exists($image, 'width') ? (int) $image->width() : 0;
        $h = method_exists($image, 'height') ? (int) $image->height() : 0;

        if ($w <= self::IMAGE_MAX_SIZE && $h <= self::IMAGE_MAX_SIZE) {
            return $image;
        }

        if (method_exists($image, 'coverDown')) {
            return $image->coverDown(self::IMAGE_MAX_SIZE, self::IMAGE_MAX_SIZE);
        }
        if (method_exists($image, 'cover')) {
            return $image->cover(self::IMAGE_MAX_SIZE, self::IMAGE_MAX_SIZE);
        }
        if (method_exists($image, 'scale')) {
            $image->scale(self::IMAGE_MAX_SIZE, self::IMAGE_MAX_SIZE);
        }

        return $image;
    }

    private function applyWatermarkVisible(object $image): void
    {
        if (! method_exists($image, 'text')) {
            return;
        }

        $width = method_exists($image, 'width') ? (int) $image->width() : 1200;
        $height = method_exists($image, 'height') ? (int) $image->height() : 800;

        $fontSize = (int) max(32, min(120, round($width / 12)));
        $padding = (int) max(24, round($width / 40));
        $x = max(0, $width - $padding);
        $y = max(0, $height - $padding);

        try {
            $image->text('Fun Out', $x - 2, $y - 2, function ($font) use ($fontSize): void {
                if (method_exists($font, 'size')) {
                    $font->size($fontSize);
                }
                if (method_exists($font, 'color')) {
                    $font->color('rgba(0,0,0,0.4)');
                }
                if (method_exists($font, 'align')) {
                    $font->align('right');
                }
                if (method_exists($font, 'valign')) {
                    $font->valign('bottom');
                }
            });

            $image->text('Fun Out', $x, $y, function ($font) use ($fontSize): void {
                if (method_exists($font, 'size')) {
                    $font->size($fontSize);
                }
                if (method_exists($font, 'color')) {
                    $font->color('#3a3a3a');
                }
                if (method_exists($font, 'align')) {
                    $font->align('right');
                }
                if (method_exists($font, 'valign')) {
                    $font->valign('bottom');
                }
            });
        } catch (\Throwable) {
            //
        }
    }

    private function ensureDirectoryFor(string $path): void
    {
        $dir = storage_path('app/private/'.dirname($path));
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    private function createMedia(User $owner, string $path, string $originalName, ?string $mimeType, string $category): Media
    {
        $fullPath = storage_path('app/private/'.$path);
        $size = is_file($fullPath) ? (int) filesize($fullPath) : 0;

        return Media::query()->create([
            'model_type' => User::class,
            'model_id' => $owner->id,
            'collection_name' => 'private_assets_'.$category,
            'file_name' => $originalName,
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $mimeType,
            'size' => $size,
            'custom_properties' => ['category' => $category],
        ]);
    }
}
