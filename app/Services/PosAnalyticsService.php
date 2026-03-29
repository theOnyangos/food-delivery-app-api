<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyMenu;
use App\Models\PosSale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PosAnalyticsService
{
    private const MAX_RANGE_DAYS = 366;

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    public function parseDateRange(Request $request): array
    {
        $fromInput = $request->query('from');
        $toInput = $request->query('to');

        try {
            $from = $fromInput !== null && $fromInput !== ''
                ? Carbon::parse((string) $fromInput)->startOfDay()
                : now()->subDays(30)->startOfDay();
            $to = $toInput !== null && $toInput !== ''
                ? Carbon::parse((string) $toInput)->endOfDay()
                : now()->endOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'from' => ['Invalid from or to date.'],
            ]);
        }

        if ($from->gt($to)) {
            throw ValidationException::withMessages([
                'from' => ['`from` must be before or equal to `to`.'],
            ]);
        }

        if ($from->diffInDays($to) > self::MAX_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'to' => ['Date range cannot exceed '.self::MAX_RANGE_DAYS.' days.'],
            ]);
        }

        return ['from' => $from, 'to' => $to];
    }

    /**
     * @return Collection<int, PosSale>
     */
    public function salesInRange(Carbon $from, Carbon $to): Collection
    {
        return PosSale::query()
            ->whereBetween('created_at', [$from, $to])
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Carbon $from, Carbon $to, bool $includeDailySeries): array
    {
        $sales = $this->salesInRange($from, $to);

        $orderCount = $sales->count();
        $revenueTotal = 0.0;
        $taxTotal = 0.0;
        $itemsSold = 0;

        $dailyRevenue = [];
        $dailyOrders = [];

        foreach ($sales as $sale) {
            $totals = is_array($sale->totals) ? $sale->totals : [];
            $rev = (float) ($totals['total'] ?? 0);
            $tax = (float) ($totals['tax'] ?? 0);
            $revenueTotal += $rev;
            $taxTotal += $tax;

            $lines = is_array($sale->lines) ? $sale->lines : [];
            foreach ($lines as $line) {
                if (! is_array($line)) {
                    continue;
                }
                $itemsSold += (int) ($line['quantity'] ?? 0);
            }

            if ($includeDailySeries) {
                $dayKey = $sale->created_at?->toDateString() ?? '';
                if ($dayKey !== '') {
                    $dailyRevenue[$dayKey] = ($dailyRevenue[$dayKey] ?? 0.0) + $rev;
                    $dailyOrders[$dayKey] = ($dailyOrders[$dayKey] ?? 0) + 1;
                }
            }
        }

        $aov = $orderCount > 0 ? round($revenueTotal / $orderCount, 2) : 0.0;

        $out = [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'order_count' => $orderCount,
            'revenue_total' => round($revenueTotal, 2),
            'tax_total' => round($taxTotal, 2),
            'items_sold_total' => $itemsSold,
            'average_order_value' => $aov,
        ];

        if ($includeDailySeries) {
            $daily = [];
            $cursor = $from->copy()->startOfDay();
            $endDay = $to->copy()->startOfDay();
            while ($cursor->lte($endDay)) {
                $key = $cursor->toDateString();
                $daily[] = [
                    'date' => $key,
                    'revenue' => round($dailyRevenue[$key] ?? 0.0, 2),
                    'orders' => $dailyOrders[$key] ?? 0,
                ];
                $cursor->addDay();
            }
            $out['daily'] = $daily;
        }

        return $out;
    }

    /**
     * @return array{rows: list<array<string, mixed>>}
     */
    public function byMenu(Carbon $from, Carbon $to): array
    {
        $sales = $this->salesInRange($from, $to);

        $byMenuId = [];
        foreach ($sales as $sale) {
            $mid = $sale->daily_menu_id;
            if ($mid === null) {
                $mid = '';
            }
            if (! isset($byMenuId[$mid])) {
                $byMenuId[$mid] = ['order_count' => 0, 'revenue_total' => 0.0];
            }
            $byMenuId[$mid]['order_count']++;
            $totals = is_array($sale->totals) ? $sale->totals : [];
            $byMenuId[$mid]['revenue_total'] += (float) ($totals['total'] ?? 0);
        }

        $menuIds = array_filter(array_keys($byMenuId), static fn ($id) => $id !== '');
        $menus = DailyMenu::query()->whereIn('id', $menuIds)->get()->keyBy('id');

        $rows = [];
        foreach ($byMenuId as $menuId => $agg) {
            $row = [
                'daily_menu_id' => $menuId === '' ? null : $menuId,
                'menu_date' => null,
                'menu_status' => null,
                'order_count' => $agg['order_count'],
                'revenue_total' => round($agg['revenue_total'], 2),
            ];
            if ($menuId !== '' && $menus->has($menuId)) {
                $m = $menus->get($menuId);
                $row['menu_date'] = $m->menu_date?->toDateString();
                $row['menu_status'] = $m->status;
            }
            $rows[] = $row;
        }

        usort($rows, static fn ($a, $b) => ($b['revenue_total'] <=> $a['revenue_total']));

        return ['rows' => array_values($rows)];
    }

    /**
     * @return array{rows: list<array<string, mixed>>}
     */
    public function bySalesperson(Carbon $from, Carbon $to): array
    {
        $sales = $this->salesInRange($from, $to);

        $byUser = [];
        foreach ($sales as $sale) {
            $uid = (string) $sale->sold_by;
            if (! isset($byUser[$uid])) {
                $byUser[$uid] = ['order_count' => 0, 'revenue_total' => 0.0];
            }
            $byUser[$uid]['order_count']++;
            $totals = is_array($sale->totals) ? $sale->totals : [];
            $byUser[$uid]['revenue_total'] += (float) ($totals['total'] ?? 0);
        }

        $userIds = array_keys($byUser);
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        $rows = [];
        foreach ($byUser as $userId => $agg) {
            $u = $users->get($userId);
            $name = '—';
            $email = null;
            if ($u !== null) {
                $name = trim(($u->first_name ?? '').' '.($u->last_name ?? ''));
                if ($name === '') {
                    $name = $u->email ?? '—';
                }
                $email = $u->email;
            }
            $rows[] = [
                'user_id' => $userId,
                'display_name' => $name,
                'email' => $email,
                'order_count' => $agg['order_count'],
                'revenue_total' => round($agg['revenue_total'], 2),
            ];
        }

        usort($rows, static fn ($a, $b) => ($b['revenue_total'] <=> $a['revenue_total']));

        return ['rows' => array_values($rows)];
    }

    /**
     * @return array{rows: list<array<string, mixed>>}
     */
    public function meals(Carbon $from, Carbon $to, string $sort, int $limit): array
    {
        $sales = $this->salesInRange($from, $to);

        $byMeal = [];
        foreach ($sales as $sale) {
            $lines = is_array($sale->lines) ? $sale->lines : [];
            foreach ($lines as $line) {
                if (! is_array($line)) {
                    continue;
                }
                $mealId = (string) ($line['meal_id'] ?? '');
                if ($mealId === '') {
                    continue;
                }
                $title = (string) ($line['meal_title'] ?? '—');
                $qty = (int) ($line['quantity'] ?? 0);
                $rev = (float) ($line['line_effective_total'] ?? 0);
                if (! isset($byMeal[$mealId])) {
                    $byMeal[$mealId] = [
                        'meal_id' => $mealId,
                        'meal_title' => $title,
                        'quantity_sold' => 0,
                        'revenue_total' => 0.0,
                    ];
                }
                $byMeal[$mealId]['quantity_sold'] += $qty;
                $byMeal[$mealId]['revenue_total'] += $rev;
                $byMeal[$mealId]['meal_title'] = $title;
            }
        }

        $rows = array_values($byMeal);
        foreach ($rows as &$r) {
            $r['revenue_total'] = round($r['revenue_total'], 2);
        }
        unset($r);

        if ($sort === 'quantity') {
            usort($rows, static fn ($a, $b) => ($b['quantity_sold'] <=> $a['quantity_sold']));
        } else {
            usort($rows, static fn ($a, $b) => ($b['revenue_total'] <=> $a['revenue_total']));
        }

        $rows = array_slice($rows, 0, max(1, min(100, $limit)));

        return ['rows' => $rows];
    }
}
