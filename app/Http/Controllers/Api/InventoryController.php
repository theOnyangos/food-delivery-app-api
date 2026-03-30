<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ImportInventoryCsvRequest;
use App\Http\Requests\Inventory\InventoryItemAnalyticsRequest;
use App\Http\Requests\Inventory\RecordInventoryUsageRequest;
use App\Http\Requests\Inventory\StoreInventoryItemRequest;
use App\Http\Requests\Inventory\UpdateInventoryItemRequest;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {}

    public function summary(): JsonResponse
    {
        return $this->apiSuccess($this->inventoryService->summary(), 'Inventory summary fetched successfully.');
    }

    public function itemsDataTables(Request $request): mixed
    {
        return $this->inventoryService->getDataTables($request);
    }

    public function itemOptions(): JsonResponse
    {
        return $this->apiSuccess($this->inventoryService->listItemOptions(), 'Inventory items fetched successfully.');
    }

    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $userId = $request->user()?->id;
        $item = $this->inventoryService->createItem($request->validated(), $userId);

        return $this->apiSuccess(
            $this->inventoryService->formatItem($item),
            'Inventory item created successfully.',
            201
        );
    }

    public function show(InventoryItem $inventoryItem): JsonResponse
    {
        return $this->apiSuccess(
            $this->inventoryService->formatItem($inventoryItem),
            'Inventory item fetched successfully.'
        );
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        $userId = $request->user()?->id;
        $updated = $this->inventoryService->updateItem($inventoryItem, $request->validated(), $userId);

        return $this->apiSuccess(
            $this->inventoryService->formatItem($updated),
            'Inventory item updated successfully.'
        );
    }

    public function destroy(InventoryItem $inventoryItem): JsonResponse
    {
        $this->inventoryService->deleteItem($inventoryItem);

        return $this->apiSuccess(null, 'Inventory item deleted successfully.');
    }

    public function movements(InventoryItem $inventoryItem): JsonResponse
    {
        return $this->apiSuccess(
            $this->inventoryService->listMovementsForItem($inventoryItem),
            'Movements fetched successfully.'
        );
    }

    public function analytics(InventoryItemAnalyticsRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        $from = Carbon::parse((string) $request->validated('from'))->startOfDay();
        $to = Carbon::parse((string) $request->validated('to'))->startOfDay();

        return $this->apiSuccess(
            $this->inventoryService->itemAnalytics($inventoryItem, $from, $to),
            'Inventory analytics fetched successfully.'
        );
    }

    public function recordUsage(RecordInventoryUsageRequest $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if ($userId === null) {
            return $this->apiError('Unauthenticated.', 401);
        }

        $occurredAt = $request->filled('occurred_at')
            ? Carbon::parse((string) $request->input('occurred_at'))
            : now();

        $result = $this->inventoryService->recordUsage(
            $request->input('lines', []),
            $occurredAt,
            $request->input('notes'),
            $userId
        );

        return $this->apiSuccess($result, 'Usage recorded successfully.');
    }

    public function import(ImportInventoryCsvRequest $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if ($userId === null) {
            return $this->apiError('Unauthenticated.', 401);
        }

        $file = $request->file('file');
        if ($file === null) {
            return $this->apiError('File is required.', 422);
        }

        $result = $this->inventoryService->importFromCsv($file, $userId);

        return $this->apiSuccess($result, 'Import completed.');
    }

    public function exportCsv(): StreamedResponse
    {
        return $this->inventoryService->exportCsvStream();
    }

    public function templateCsv(): StreamedResponse
    {
        return $this->inventoryService->templateCsvStream();
    }
}
