<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\PosSaleCompleted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StorePosSaleRequest;
use App\Models\DailyMenu;
use App\Models\PosSale;
use App\Services\DailyMenuService;
use App\Services\PosSaleAdminService;
use App\Services\PosSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function __construct(
        private readonly DailyMenuService $dailyMenuService,
        private readonly PosSaleService $posSaleService,
        private readonly PosSaleAdminService $posSaleAdminService
    ) {}

    public function todayDailyMenu(): JsonResponse
    {
        $menu = $this->posSaleService->todayPublishedMenu();
        if ($menu === null) {
            return $this->apiError('No published menu for today.', 404);
        }

        return $this->apiSuccess(
            $this->dailyMenuService->formatAdminDetail($menu),
            'Today\'s published daily menu.'
        );
    }

    public function publishedMenusForPos(): JsonResponse
    {
        $menus = DailyMenu::query()
            ->published()
            ->orderByDesc('menu_date')
            ->limit(90)
            ->get(['id', 'menu_date', 'status']);

        return $this->apiSuccess(
            [
                'menus' => $menus->map(static fn (DailyMenu $m): array => [
                    'id' => $m->id,
                    'menu_date' => $m->menu_date?->toDateString(),
                    'status' => $m->status,
                ]),
            ],
            'Published daily menus for POS.'
        );
    }

    public function storeSale(StorePosSaleRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return $this->apiError('Unauthenticated.', 401);
        }

        $validated = $request->validated();
        $requestedMenuId = isset($validated['daily_menu_id']) ? (string) $validated['daily_menu_id'] : null;
        $menu = $this->posSaleService->resolveMenuForPosSale($requestedMenuId);
        if ($menu === null) {
            return $this->apiError(
                $requestedMenuId !== null && $requestedMenuId !== ''
                    ? 'No published daily menu found for that id.'
                    : 'No published menu for today.',
                404
            );
        }

        $lines = array_map(static fn (array $row): array => [
            'meal_id' => $row['meal_id'],
            'quantity' => (int) $row['quantity'],
        ], $validated['lines']);

        $sale = $this->posSaleService->createSale(
            $menu,
            (string) $user->id,
            $validated['order_type'],
            isset($validated['customer_email']) ? (string) $validated['customer_email'] : null,
            $lines
        );

        $sale->load('soldByUser');
        event(new PosSaleCompleted($sale));

        $this->dailyMenuService->invalidateCache();

        return $this->apiSuccess(
            [
                'sale' => [
                    'id' => $sale->id,
                    'receipt_number' => $sale->receipt_number,
                    'order_type' => $sale->order_type,
                    'customer_email' => $sale->customer_email,
                    'created_at' => $sale->created_at?->toIso8601String(),
                ],
                'totals' => $sale->totals,
                'lines' => $sale->lines,
            ],
            'Sale recorded.',
            201
        );
    }

    public function salesDataTables(Request $request): mixed
    {
        return $this->posSaleAdminService->getDataTables($request);
    }

    public function destroySale(PosSale $posSale): JsonResponse
    {
        DB::transaction(function () use ($posSale): void {
            $this->posSaleService->restoreServingsForDeletedSale($posSale);
            $posSale->delete();
        });

        $this->dailyMenuService->invalidateCache();

        return $this->apiSuccess(null, 'POS sale deleted.');
    }

    public function showSale(PosSale $posSale): JsonResponse
    {
        $posSale->load(['soldByUser', 'dailyMenu']);

        return $this->apiSuccess([
            'id' => $posSale->id,
            'receipt_number' => $posSale->receipt_number,
            'order_type' => $posSale->order_type,
            'daily_menu_id' => $posSale->daily_menu_id,
            'sold_by' => $posSale->sold_by,
            'sold_by_name' => $posSale->soldByUser === null
                ? null
                : (trim(($posSale->soldByUser->first_name ?? '').' '.($posSale->soldByUser->last_name ?? '')) ?: null),
            'sold_by_email' => $posSale->soldByUser?->email,
            'customer_email' => $posSale->customer_email,
            'totals' => $posSale->totals,
            'lines' => $posSale->lines,
            'created_at' => $posSale->created_at?->toIso8601String(),
        ], 'POS sale detail.');
    }
}
