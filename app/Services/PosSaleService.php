<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyMenu;
use App\Models\DailyMenuItem;
use App\Models\PosSale;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosSaleService
{
    private const TAX_RATE = 0.08;

    private const VOLUME_MIN_LINE_ITEMS = 5;

    private const VOLUME_EXTRA_DISCOUNT_PCT = 2;

    public function todayPublishedMenu(): ?DailyMenu
    {
        $today = Carbon::today();

        return DailyMenu::query()
            ->published()
            ->whereDate('menu_date', $today)
            ->first();
    }

    /**
     * Resolve which published daily menu applies to a POS checkout.
     * If {@see $dailyMenuId} is set, that menu must exist and be published; otherwise today's published menu is used.
     */
    public function resolveMenuForPosSale(?string $dailyMenuId): ?DailyMenu
    {
        if ($dailyMenuId !== null && $dailyMenuId !== '') {
            return DailyMenu::query()
                ->published()
                ->whereKey($dailyMenuId)
                ->first();
        }

        return $this->todayPublishedMenu();
    }

    /**
     * @param  list<array{meal_id: string, quantity: int}>  $lines
     * @return array<string, int> meal_id => total quantity
     */
    public function mergeSaleLines(array $lines): array
    {
        $merged = [];
        foreach ($lines as $line) {
            $mid = $line['meal_id'];
            $merged[$mid] = ($merged[$mid] ?? 0) + (int) $line['quantity'];
        }

        return $merged;
    }

    /**
     * @param  array<string, int>  $mergedQtyByMealId
     * @param  Collection<string, DailyMenuItem>  $itemsByMealId
     * @return array{totals: array<string, float|int>, lines: list<array<string, mixed>>}
     */
    public function buildSalePayloadFromMerged(array $mergedQtyByMealId, Collection $itemsByMealId): array
    {
        $snapshotLines = [];
        $listSubtotal = 0.0;
        $effectiveSubtotal = 0.0;
        $totalLineItems = 0;

        foreach ($mergedQtyByMealId as $mealId => $qty) {
            /** @var DailyMenuItem|null $row */
            $row = $itemsByMealId->get($mealId);
            if ($row === null) {
                throw ValidationException::withMessages([
                    'lines' => ['Meal '.$mealId.' is not on today\'s menu.'],
                ]);
            }

            $max = $row->max_per_order;
            if ($max !== null && $qty > $max) {
                throw ValidationException::withMessages([
                    'lines' => ['Quantity exceeds max per order for '.$mealId.'.'],
                ]);
            }

            if ($qty > $row->servings_available) {
                throw ValidationException::withMessages([
                    'lines' => ['Not enough servings available for '.$mealId.'.'],
                ]);
            }

            $price = $row->price;
            $listUnit = $price === null || $price === '' ? 0.0 : round((float) (string) $price, 2);
            $eff = DailyMenuService::effectiveUnitPrice($price, $row->discount_percent);
            $unitEffective = $eff !== null && $eff > 0 ? $eff : $listUnit;

            $lineList = round($listUnit * $qty, 2);
            $lineEff = round($unitEffective * $qty, 2);

            $listSubtotal += $lineList;
            $effectiveSubtotal += $lineEff;
            $totalLineItems += $qty;

            $meal = $row->meal;
            $snapshotLines[] = [
                'meal_id' => $mealId,
                'meal_title' => $meal?->title ?? '—',
                'meal_thumbnail_image' => $meal?->thumbnail_image,
                'meal_excerpt' => $meal?->excerpt,
                'meal_category_title' => $meal?->category?->title,
                'quantity' => $qty,
                'unit_list_price' => $listUnit,
                'unit_effective_price' => $unitEffective,
                'line_list_total' => $lineList,
                'line_effective_total' => $lineEff,
            ];
        }

        $menuDiscount = max(0.0, round($listSubtotal - $effectiveSubtotal, 2));

        $volumeDiscount = 0.0;
        if ($totalLineItems >= self::VOLUME_MIN_LINE_ITEMS && $effectiveSubtotal > 0) {
            $volumeDiscount = round($effectiveSubtotal * (self::VOLUME_EXTRA_DISCOUNT_PCT / 100), 2);
        }

        $taxableSubtotal = max(0.0, round($effectiveSubtotal - $volumeDiscount, 2));
        $tax = round($taxableSubtotal * self::TAX_RATE, 2);
        $total = round($taxableSubtotal + $tax, 2);

        $totals = [
            'list_subtotal' => round($listSubtotal, 2),
            'menu_discount' => $menuDiscount,
            'effective_subtotal' => round($effectiveSubtotal, 2),
            'volume_discount' => $volumeDiscount,
            'taxable_subtotal' => $taxableSubtotal,
            'tax' => $tax,
            'total' => $total,
            'total_line_items' => $totalLineItems,
        ];

        return [
            'totals' => $totals,
            'lines' => $snapshotLines,
        ];
    }

    /**
     * @param  list<array{meal_id: string, quantity: int}>  $lines
     * @return array{totals: array<string, float|int>, lines: list<array<string, mixed>>}
     */
    public function buildSalePayload(DailyMenu $menu, array $lines): array
    {
        $menu->load(['items.meal.category']);
        $merged = $this->mergeSaleLines($lines);
        $byMealId = $menu->items->keyBy('meal_id');

        return $this->buildSalePayloadFromMerged($merged, $byMealId);
    }

    public function generateReceiptNumber(): string
    {
        $prefix = 'ASL-Order-'.now()->format('Ymd').'-';

        for ($i = 0; $i < 12; $i++) {
            $candidate = $prefix.strtoupper(bin2hex(random_bytes(3)));
            if (! PosSale::query()->where('receipt_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $prefix.strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * @param  list<array{meal_id: string, quantity: int}>  $lines
     */
    public function createSale(
        DailyMenu $menu,
        string $soldByUserId,
        string $orderType,
        ?string $customerEmail,
        array $lines
    ): PosSale {
        $merged = $this->mergeSaleLines($lines);

        return DB::transaction(function () use ($menu, $merged, $soldByUserId, $orderType, $customerEmail): PosSale {
            $mealIds = array_keys($merged);
            sort($mealIds);

            $lockedItems = DailyMenuItem::query()
                ->where('daily_menu_id', $menu->id)
                ->whereIn('meal_id', $mealIds)
                ->orderBy('meal_id')
                ->lockForUpdate()
                ->get();

            if ($lockedItems->count() !== count($mealIds)) {
                throw ValidationException::withMessages([
                    'lines' => ['One or more meals are not on this menu.'],
                ]);
            }

            $byMealId = $lockedItems->keyBy('meal_id');
            $lockedItems->load(['meal.category']);

            $built = $this->buildSalePayloadFromMerged($merged, $byMealId);

            foreach ($merged as $mealId => $qty) {
                /** @var DailyMenuItem $row */
                $row = $byMealId->get($mealId);
                $row->decrement('servings_available', $qty);
            }

            return PosSale::query()->create([
                'receipt_number' => $this->generateReceiptNumber(),
                'daily_menu_id' => $menu->id,
                'sold_by' => $soldByUserId,
                'order_type' => $orderType,
                'customer_email' => $customerEmail,
                'totals' => $built['totals'],
                'lines' => $built['lines'],
            ]);
        });
    }

    /**
     * Put servings back when a POS sale row is removed (e.g. admin delete).
     */
    public function restoreServingsForDeletedSale(PosSale $sale): void
    {
        $menuId = $sale->daily_menu_id;
        $lines = is_array($sale->lines) ? $sale->lines : [];
        if ($menuId === null || $lines === []) {
            return;
        }

        $merged = [];
        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }
            $mid = (string) ($line['meal_id'] ?? '');
            if ($mid === '') {
                continue;
            }
            $merged[$mid] = ($merged[$mid] ?? 0) + (int) ($line['quantity'] ?? 0);
        }

        if ($merged === []) {
            return;
        }

        $mealIds = array_keys($merged);
        sort($mealIds);

        $items = DailyMenuItem::query()
            ->where('daily_menu_id', $menuId)
            ->whereIn('meal_id', $mealIds)
            ->orderBy('meal_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('meal_id');

        foreach ($merged as $mealId => $qty) {
            $row = $items->get($mealId);
            if ($row !== null) {
                $row->increment('servings_available', $qty);
            }
        }
    }
}
