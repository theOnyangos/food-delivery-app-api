<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatSupportAllocationRequest;
use App\Services\ChatSupportAllocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatSupportAllocationController extends Controller
{
    public function __construct(
        protected ChatSupportAllocationService $allocationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $supportUserId = $request->query('support_user_id');
            $vendorUserId = $request->query('vendor_user_id');
            $perPage = (int) $request->query('per_page', 15);
            $allocations = $this->allocationService->listAllocations($supportUserId, $vendorUserId, $perPage);

            return response()->json([
                'success' => true,
                'message' => 'Support allocations retrieved successfully',
                'data' => $allocations->items(),
                'meta' => [
                    'current_page' => $allocations->currentPage(),
                    'last_page' => $allocations->lastPage(),
                    'per_page' => $allocations->perPage(),
                    'total' => $allocations->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error listing support allocations: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to list support allocations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreChatSupportAllocationRequest $request): JsonResponse
    {
        try {
            $allocation = $this->allocationService->assign(
                $request->validated('support_user_id'),
                $request->validated('vendor_user_id'),
            );
            $allocation->load(['supportUser:id,first_name,middle_name,last_name,email', 'vendorUser:id,first_name,middle_name,last_name,email']);

            return response()->json([
                'success' => true,
                'message' => 'Support allocation created successfully',
                'data' => $allocation,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error creating support allocation: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create support allocation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            if (! $this->allocationService->unassign($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Support allocation not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Support allocation removed successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error removing support allocation: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove support allocation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
