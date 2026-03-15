<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageUploadService
{
    private const DEFAULT_THUMBNAILS = [
        ['width' => 300, 'height' => 300],
        ['width' => 600, 'height' => 600],
    ];

    /**
     * @param  array{width?: int|null, height?: int|null, watermark?: bool|null}  $options
     * @return array{
     *   original: array{media_id: string, path: string},
     *   thumbnails: array<int, array{media_id: string, path: string, width: int, height: int}>,
     *   watermarked?: array{media_id: string, path: string}
     * }
     */
    public function upload(User $owner, UploadedFile $file, array $options = []): array
    {
        $extension = $file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'jpg');
        $baseName = (string) Str::uuid();
        $originalPath = 'private/uploads/'.$baseName.'.'.$extension;

        if (class_exists(\Intervention\Image\Laravel\Facades\Image::class)) {
            $image = \Intervention\Image\Laravel\Facades\Image::read($file);
            $image = $this->resizeFromOptions($image, $options);

            $this->ensureDirectoryFor($originalPath);
            $image->save(storage_path('app/'.$originalPath));
        } else {
            $this->ensureDirectoryFor($originalPath);
            $file->storeAs(dirname($originalPath), basename($originalPath), 'local');
        }

        $originalMedia = $this->createMediaRecord(
            owner: $owner,
            path: $originalPath,
            originalFileName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType(),
            collectionName: 'uploads',
            custom: [
                'variant' => 'original',
                'requested_width' => $options['width'] ?? null,
                'requested_height' => $options['height'] ?? null,
            ],
        );

        $thumbnails = [];
        foreach (self::DEFAULT_THUMBNAILS as $thumb) {
            $thumbPath = 'private/uploads/thumbnails/'.$baseName.'_'.$thumb['width'].'x'.$thumb['height'].'.'.$extension;

            if (class_exists(\Intervention\Image\Laravel\Facades\Image::class)) {
                $thumbImage = \Intervention\Image\Laravel\Facades\Image::read(storage_path('app/'.$originalPath));
                $thumbImage = $this->coverToSize($thumbImage, $thumb['width'], $thumb['height']);
                $this->ensureDirectoryFor($thumbPath);
                $thumbImage->save(storage_path('app/'.$thumbPath));
            } else {
                $this->ensureDirectoryFor($thumbPath);
                copy(storage_path('app/'.$originalPath), storage_path('app/'.$thumbPath));
            }

            $thumbMedia = $this->createMediaRecord(
                owner: $owner,
                path: $thumbPath,
                originalFileName: $file->getClientOriginalName(),
                mimeType: $file->getMimeType(),
                collectionName: 'uploads_thumbnails',
                custom: [
                    'variant' => 'thumbnail',
                    'width' => $thumb['width'],
                    'height' => $thumb['height'],
                ],
            );

            $thumbnails[] = [
                'media_id' => $thumbMedia->id,
                'path' => $thumbPath,
                'width' => $thumb['width'],
                'height' => $thumb['height'],
            ];
        }

        $result = [
            'original' => [
                'media_id' => $originalMedia->id,
                'path' => $originalPath,
            ],
            'thumbnails' => $thumbnails,
        ];

        if (! empty($options['watermark'])) {
            $watermarkedPath = 'private/uploads/watermarked/'.$baseName.'.'.$extension;

            if (class_exists(\Intervention\Image\Laravel\Facades\Image::class)) {
                $wm = \Intervention\Image\Laravel\Facades\Image::read(storage_path('app/'.$originalPath));
                $this->applyWatermark($wm);
                $this->ensureDirectoryFor($watermarkedPath);
                $wm->save(storage_path('app/'.$watermarkedPath));
            } else {
                $this->ensureDirectoryFor($watermarkedPath);
                copy(storage_path('app/'.$originalPath), storage_path('app/'.$watermarkedPath));
            }

            $wmMedia = $this->createMediaRecord(
                owner: $owner,
                path: $watermarkedPath,
                originalFileName: $file->getClientOriginalName(),
                mimeType: $file->getMimeType(),
                collectionName: 'uploads_watermarked',
                custom: [
                    'variant' => 'watermarked',
                    'watermark_text' => 'fun-out',
                ],
            );

            $result['watermarked'] = [
                'media_id' => $wmMedia->id,
                'path' => $watermarkedPath,
            ];
        }

        return $result;
    }

    /**
     * @param  array{width?: int|null, height?: int|null}  $options
     */
    private function resizeFromOptions(object $image, array $options): object
    {
        $width = isset($options['width']) ? (int) $options['width'] : null;
        $height = isset($options['height']) ? (int) $options['height'] : null;

        if ($width || $height) {
            $image->scale($width, $height);

            return $image;
        }

        $image->scale(1920, 1920);

        return $image;
    }

    private function coverToSize(object $image, int $width, int $height): object
    {
        if (method_exists($image, 'coverDown')) {
            return $image->coverDown($width, $height);
        }

        if (method_exists($image, 'cover')) {
            return $image->cover($width, $height);
        }

        $image->scale($width, $height);

        return $image;
    }

    private function applyWatermark(object $image): void
    {
        if (! method_exists($image, 'text')) {
            return;
        }

        $padding = 18;
        $width = method_exists($image, 'width') ? (int) $image->width() : 1200;
        $height = method_exists($image, 'height') ? (int) $image->height() : 800;

        $fontSize = (int) max(18, min(48, round($width / 22)));
        $x = max(0, $width - $padding);
        $y = max(0, $height - $padding);

        try {
            $image->text('fun-out', $x - 1, $y - 1, function ($font) use ($fontSize) {
                if (method_exists($font, 'size')) {
                    $font->size($fontSize);
                }
                if (method_exists($font, 'color')) {
                    $font->color('rgba(0, 63, 123, 0.25)');
                }
                if (method_exists($font, 'align')) {
                    $font->align('right');
                }
                if (method_exists($font, 'valign')) {
                    $font->valign('bottom');
                }
            });

            $image->text('fun-out', $x, $y, function ($font) use ($fontSize) {
                if (method_exists($font, 'size')) {
                    $font->size($fontSize);
                }
                if (method_exists($font, 'color')) {
                    $font->color('rgba(244, 78, 26, 0.22)');
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

    private function ensureDirectoryFor(string $relativePath): void
    {
        $fullPath = storage_path('app/'.dirname($relativePath));
        if (! is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
    }

    /**
     * @param  array<string, mixed>  $custom
     */
    private function createMediaRecord(
        User $owner,
        string $path,
        string $originalFileName,
        ?string $mimeType,
        string $collectionName,
        array $custom = [],
    ): Media {
        $absolutePath = storage_path('app/'.$path);
        $size = is_file($absolutePath) ? (int) filesize($absolutePath) : 0;

        return Media::query()->create([
            'model_type' => User::class,
            'model_id' => $owner->id,
            'collection_name' => $collectionName,
            'file_name' => $originalFileName,
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $mimeType,
            'size' => $size,
            'custom_properties' => $custom,
        ]);
    }
}
