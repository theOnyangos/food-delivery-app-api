<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\ImageUploadService;
use App\Services\PrivateAssetUploadService;
use App\Services\PublicImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UploadController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $imageUploadService,
        private readonly PublicImageUploadService $publicImageUploadService,
        private readonly PrivateAssetUploadService $privateAssetUploadService
    ) {}

    public function image(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,gif,webp', 'max:10240'],
            'width' => ['nullable', 'integer', 'min:1', 'max:6000'],
            'height' => ['nullable', 'integer', 'min:1', 'max:6000'],
            'watermark' => ['nullable', 'boolean'],
        ]);

        $result = $this->imageUploadService->upload(
            owner: $request->user(),
            file: $request->file('image'),
            options: [
                'width' => $request->integer('width') ?: null,
                'height' => $request->integer('height') ?: null,
                'watermark' => (bool) $request->boolean('watermark', false),
            ],
        );

        return $this->apiSuccess($result, 'Image uploaded successfully.', 201);
    }

    public function publicAsset(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:10240',
            ],
            'width' => ['nullable', 'integer', 'min:1', 'max:6000'],
            'height' => ['nullable', 'integer', 'min:1', 'max:6000'],
            'watermark' => ['nullable', Rule::in([true, false, 1, 0, '1', '0', 'true', 'false'])],
        ]);

        $watermark = $request->filled('watermark') && in_array(
            strtolower((string) $request->input('watermark')),
            ['1', 'true'],
            true
        );

        $result = $this->publicImageUploadService->upload(
            owner: $request->user(),
            file: $request->file('file'),
            options: [
                'width' => $request->integer('width') ?: null,
                'height' => $request->integer('height') ?: null,
                'watermark' => $watermark,
            ],
        );

        return $this->apiSuccess($result, 'Public asset uploaded successfully.', 201);
    }

    public function privateAsset(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'category' => ['nullable', 'string', 'max:64'],
        ]);

        $category = $request->input('category', 'general');
        $file = $request->file('file');

        $result = $this->privateAssetUploadService->upload(
            $request->user(),
            $file,
            $category
        );

        return $this->apiSuccess([
            'id' => $result['id'],
            'url' => $result['url'],
        ], 'File uploaded successfully.', 201);
    }

    public function serveUrl(Request $request, Media $media): JsonResponse
    {
        $this->authorizeMediaOwnership($request, $media);

        $url = (string) preg_replace(
            '#(?<!:)//+#',
            '/',
            (string) URL::temporarySignedRoute(
                'uploads.serve',
                now()->addHour(),
                ['media' => $media->id],
                absolute: true
            )
        );

        return response()->json([
            'success' => true,
            'message' => 'URL generated successfully.',
            'data' => [
                'url' => $url,
                'expires_in_seconds' => 3600,
            ],
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        $this->authorizeMediaOwnership($request, $media);

        $path = $media->path;
        $disk = $media->disk ?: config('filesystems.default');

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }

        $media->delete();

        return $this->apiSuccess(null, 'File deleted successfully.');
    }

    public function destroyByPath(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string', 'max:512'],
        ]);

        $pathOrUrl = (string) $request->query('path', $request->input('path', ''));
        $path = $this->extractPathFromUrlOrPath($pathOrUrl);
        if (! $path) {
            return $this->apiError('Invalid path or URL.');
        }

        $user = $request->user();
        $media = Media::query()
            ->where('path', $path)
            ->where('model_type', $user::class)
            ->where('model_id', $user->id)
            ->first();

        if (! $media) {
            return $this->apiError('File not found.', 404);
        }

        $disk = $media->disk ?: config('filesystems.default');
        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }

        $media->delete();

        return $this->apiSuccess(null, 'File deleted successfully.');
    }

    private function extractPathFromUrlOrPath(string $pathOrUrl): ?string
    {
        $s = trim($pathOrUrl);
        if (! $s) {
            return null;
        }
        if (str_contains($s, '://')) {
            $parsed = parse_url($s);
            $pathname = $parsed['path'] ?? '';

            return ltrim($pathname, '/') ?: null;
        }

        return $s;
    }

    public function serve(Request $request, Media $media): BinaryFileResponse|JsonResponse
    {
        $path = $media->path;
        $disk = $media->disk ?: config('filesystems.default');

        if (! Storage::disk($disk)->exists($path)) {
            return $this->apiError('File not found.', 404);
        }

        $fullPath = Storage::disk($disk)->path($path);

        return response()->file($fullPath, [
            'Content-Type' => $media->mime_type ?? 'application/octet-stream',
        ]);
    }

    private function authorizeMediaOwnership(Request $request, Media $media): void
    {
        $user = $request->user();
        if ($media->model_type !== $user::class || (string) $media->model_id !== (string) $user->id) {
            abort(403, 'You do not have access to this file.');
        }
    }
}
