<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyMenu;
use App\Models\DailyMenuItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyMenuService
{
    public function __construct(
        private readonly DailyMenuCacheService $dailyMenuCache
    ) {}

    /**
     * Final amount per serving after optional percentage discount.
     */
    public static function effectiveUnitPrice(float|string|null $price, float|string|null $discountPercent): ?float
    {
        if ($price === null || $price === '') {
            return null;
        }
        $p = is_numeric($price) ? (float) $price : (float) (string) $price;
        if ($p < 0 || ! is_finite($p)) {
            return null;
        }
        $d = $discountPercent === null || $discountPercent === '' ? 0.0 : (float) (string) $discountPercent;
        $d = max(0.0, min(100.0, $d));

        return round($p * (1 - $d / 100), 2);
    }

    public function invalidateCache(): void
    {
        $this->dailyMenuCache->invalidate();
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveEffective(string $dateYmd): array
    {
        $d = Carbon::parse($dateYmd)->startOfDay();

        $direct = DailyMenu::query()
            ->published()
            ->whereDate('menu_date', $d)
            ->with([
                'items' => fn ($q) => $q->orderBy('sort_order'),
                'items.meal.category',
            ])
            ->first();

        if ($direct !== null) {
            return $this->formatEffectivePayload($d, $direct, isRecycled: false);
        }

        $prior = DailyMenu::query()
            ->published()
            ->whereDate('menu_date', '<', $d)
            ->orderByDesc('menu_date')
            ->with([
                'items' => fn ($q) => $q->orderBy('sort_order'),
                'items.meal.category',
            ])
            ->first();

        if ($prior !== null) {
            return $this->formatEffectivePayload($d, $prior, isRecycled: true);
        }

        return [
            'effective_date' => $d->toDateString(),
            'is_recycled' => false,
            'source_menu_id' => null,
            'source_menu_date' => null,
            'menu' => null,
            'items' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatEffectivePayload(Carbon $effectiveDate, DailyMenu $displayMenu, bool $isRecycled): array
    {
        return [
            'effective_date' => $effectiveDate->toDateString(),
            'is_recycled' => $isRecycled,
            'source_menu_id' => $isRecycled ? $displayMenu->id : null,
            'source_menu_date' => $isRecycled ? $displayMenu->menu_date->toDateString() : null,
            'menu' => $this->serializeMenuMeta($displayMenu),
            'items' => $this->serializeItems($displayMenu),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMenuMeta(DailyMenu $menu): array
    {
        return [
            'id' => $menu->id,
            'menu_date' => $menu->menu_date->toDateString(),
            'status' => $menu->status,
            'published_at' => $menu->published_at?->toIso8601String(),
            'notes' => $menu->notes,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serializeItems(DailyMenu $menu): array
    {
        $menu->loadMissing(['items.meal.category', 'items.meal.nutrition']);

        return $menu->items->map(function (DailyMenuItem $row): array {
            $meal = $row->meal;
            $nutrition = $meal?->nutrition;

            $price = $row->price;
            $discountPercent = $row->discount_percent;

            return [
                'id' => $row->id,
                'meal_id' => $row->meal_id,
                'meal_title' => $meal?->title ?? '—',
                'meal_thumbnail_image' => $meal?->thumbnail_image,
                'meal_category_title' => $meal?->category?->title,
                'meal_excerpt' => $meal?->excerpt,
                'meal_nutrition' => $nutrition === null ? null : [
                    'fats' => $nutrition->fats,
                    'protein' => $nutrition->protein,
                    'carbs' => $nutrition->carbs,
                ],
                'sort_order' => $row->sort_order,
                'servings_available' => $row->servings_available,
                'max_per_order' => $row->max_per_order,
                'price' => $price === null ? null : round((float) (string) $price, 2),
                'discount_percent' => $discountPercent === null ? null : round((float) (string) $discountPercent, 2),
                'effective_price' => self::effectiveUnitPrice($price, $discountPercent),
            ];
        })->values()->all();
    }

    /**
     * @param  array{menu_date: string, notes?: string|null, items?: list<array<string, mixed>>}  $data
     */
    public function create(User $user, array $data): DailyMenu
    {
        return DB::transaction(function () use ($user, $data): DailyMenu {
            $menu = DailyMenu::query()->create([
                'menu_date' => $data['menu_date'],
                'status' => DailyMenu::STATUS_DRAFT,
                'created_by' => $user->id,
                'published_at' => null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncItems($menu, $data['items'] ?? []);

            $this->invalidateCache();

            return $menu->fresh(['items.meal.category', 'creator']) ?? $menu;
        });
    }

    /**
     * @param  array{menu_date?: string, notes?: string|null, items?: list<array<string, mixed>>|null}  $data
     */
    public function update(DailyMenu $menu, array $data): DailyMenu
    {
        return DB::transaction(function () use ($menu, $data): DailyMenu {
            if (isset($data['menu_date'])) {
                $menu->menu_date = $data['menu_date'];
            }
            if (array_key_exists('notes', $data)) {
                $menu->notes = $data['notes'];
            }
            $menu->save();

            if (array_key_exists('items', $data) && is_array($data['items'])) {
                $this->syncItems($menu, $data['items']);
            }

            $this->invalidateCache();

            return $menu->fresh(['items.meal.category', 'creator']) ?? $menu;
        });
    }

    /**
     * @param  list<array{meal_id: string, sort_order?: int, servings_available: int, max_per_order?: int|null, price?: float|string|null, discount_percent?: float|string|null}>  $items
     */
    private function syncItems(DailyMenu $menu, array $items): void
    {
        $menu->items()->delete();

        foreach ($items as $index => $row) {
            $price = array_key_exists('price', $row) ? $row['price'] : null;
            $discount = array_key_exists('discount_percent', $row) ? $row['discount_percent'] : null;
            if ($price === null || $price === '') {
                $discount = null;
            }

            DailyMenuItem::query()->create([
                'daily_menu_id' => $menu->id,
                'meal_id' => $row['meal_id'],
                'sort_order' => $row['sort_order'] ?? $index,
                'servings_available' => $row['servings_available'],
                'max_per_order' => $row['max_per_order'] ?? null,
                'price' => $price === '' ? null : $price,
                'discount_percent' => $discount === '' ? null : $discount,
            ]);
        }
    }

    public function publish(DailyMenu $menu): DailyMenu
    {
        $menu->status = DailyMenu::STATUS_PUBLISHED;
        $menu->published_at = now();
        $menu->save();
        $this->invalidateCache();

        return $menu->fresh(['items.meal.category', 'creator']) ?? $menu;
    }

    public function archive(DailyMenu $menu): DailyMenu
    {
        $menu->status = DailyMenu::STATUS_ARCHIVED;
        $menu->save();
        $this->invalidateCache();

        return $menu->fresh(['items.meal.category', 'creator']) ?? $menu;
    }

    public function destroyIfDraft(DailyMenu $menu): void
    {
        if ($menu->status !== DailyMenu::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft daily menus can be deleted.');
        }
        $menu->delete();
        $this->invalidateCache();
    }

    /**
     * Copy items from an existing menu into a new draft for another calendar date.
     *
     * @param  array{menu_date: string, notes?: string|null}  $data
     */
    public function duplicateAsDraft(User $user, DailyMenu $source, array $data): DailyMenu
    {
        $source->load(['items' => fn ($q) => $q->orderBy('sort_order')]);

        $items = $source->items->map(fn (DailyMenuItem $row): array => [
            'meal_id' => $row->meal_id,
            'sort_order' => $row->sort_order,
            'servings_available' => $row->servings_available,
            'max_per_order' => $row->max_per_order,
            'price' => $row->price,
            'discount_percent' => $row->discount_percent,
        ])->values()->all();

        $notes = array_key_exists('notes', $data)
            ? $data['notes']
            : $source->notes;

        return $this->create($user, [
            'menu_date' => $data['menu_date'],
            'notes' => $notes,
            'items' => $items,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function formatAdminDetail(DailyMenu $menu): array
    {
        $menu->load([
            'items' => fn ($q) => $q->orderBy('sort_order'),
            'items.meal.category',
            'items.meal.nutrition',
            'creator',
        ]);

        $creator = $menu->creator;

        return [
            'menu' => [
                'id' => $menu->id,
                'menu_date' => $menu->menu_date->toDateString(),
                'status' => $menu->status,
                'published_at' => $menu->published_at?->toIso8601String(),
                'notes' => $menu->notes,
                'created_at' => $menu->created_at?->toIso8601String(),
                'updated_at' => $menu->updated_at?->toIso8601String(),
            ],
            'creator' => $creator === null ? null : [
                'id' => $creator->id,
                'email' => $creator->email,
                'full_name' => trim(($creator->first_name ?? '').' '.($creator->last_name ?? '')) ?: null,
            ],
            'items' => $this->serializeItems($menu),
        ];
    }

    /**
     * Phase-1 stats (no order data).
     *
     * @return array<string, mixed>
     */
    public function statsSummary(?Carbon $from, ?Carbon $to, int $missingHorizonDays): array
    {
        $from = $from ?? now()->subDays(30)->startOfDay();
        $to = $to ?? now()->endOfDay();

        $publishedCount = DailyMenu::query()
            ->published()
            ->whereBetween('menu_date', [$from->toDateString(), $to->toDateString()])
            ->count();

        $draftCount = DailyMenu::query()
            ->where('status', DailyMenu::STATUS_DRAFT)
            ->whereBetween('menu_date', [$from->toDateString(), $to->toDateString()])
            ->count();

        $missingDates = [];
        $start = now()->startOfDay();
        for ($i = 0; $i < $missingHorizonDays; $i++) {
            $check = $start->copy()->addDays($i);
            $hasPublished = DailyMenu::query()
                ->published()
                ->whereDate('menu_date', $check)
                ->exists();
            if (! $hasPublished) {
                $missingDates[] = $check->toDateString();
            }
        }

        $menusInRange = DailyMenu::query()
            ->whereBetween('menu_date', [$from->toDateString(), $to->toDateString()])
            ->withCount('items')
            ->withSum('items', 'servings_available')
            ->get();

        $menusBreakdown = $menusInRange->map(function (DailyMenu $m): array {
            return [
                'id' => $m->id,
                'menu_date' => $m->menu_date->toDateString(),
                'status' => $m->status,
                'items_count' => (int) ($m->items_count ?? 0),
                'total_servings_available' => (int) ($m->items_sum_servings_available ?? 0),
            ];
        })->values()->all();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'missing_horizon_days' => $missingHorizonDays,
            'published_menus_count' => $publishedCount,
            'draft_menus_count' => $draftCount,
            'missing_published_dates_next_horizon' => $missingDates,
            'menus_in_range' => $menusBreakdown,
        ];
    }
}
