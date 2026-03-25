<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateChatSettingsRequest;
use App\Services\ChatSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ChatSettingsController extends Controller
{
    public function __construct(
        protected ChatSettingsService $settingsService
    ) {}

    public function getSettings(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getSettings();

            return response()->json([
                'success' => true,
                'message' => 'Chat settings retrieved successfully',
                'data' => $settings,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving chat settings: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve chat settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSettings(UpdateChatSettingsRequest $request): JsonResponse
    {
        try {
            $settings = $this->settingsService->updateSettings($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Chat settings updated successfully',
                'data' => $settings,
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error updating chat settings: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update chat settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
