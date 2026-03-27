<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyMenu;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DailyMenuAdminService
{
    public function getDataTables(Request $request): mixed
    {
        $query = DailyMenu::query()
            ->select('asl_daily_menus.*')
            ->leftJoin('asl_users', 'asl_daily_menus.created_by', '=', 'asl_users.id')
            ->withCount('items')
            ->orderByDesc('asl_daily_menus.menu_date');

        return DataTables::eloquent($query)
            ->addColumn('menu_date_formatted', fn (DailyMenu $row) => $row->menu_date?->format('F jS, Y') ?? '—')
            ->addColumn('creator_name', function (DailyMenu $row): string {
                $row->loadMissing('creator');
                $c = $row->creator;
                if ($c === null) {
                    return '—';
                }
                $name = trim(($c->first_name ?? '').' '.($c->last_name ?? ''));

                return $name !== '' ? $name : ($c->email ?? '—');
            })
            ->addColumn('creator_email', function (DailyMenu $row): string {
                $row->loadMissing('creator');

                return $row->creator?->email ?? '—';
            })
            ->addColumn('published_at_formatted', fn (DailyMenu $row) => $row->published_at?->format('Y-m-d H:i') ?? '—')
            ->addColumn('created_at_formatted', fn (DailyMenu $row) => $row->created_at?->format('Y-m-d H:i') ?? '—')
            ->orderColumn('menu_date_formatted', fn ($q, $order) => $q->orderBy('asl_daily_menus.menu_date', $order))
            ->orderColumn('creator_name', fn ($q, $order) => $q->orderBy('asl_users.last_name', $order)
                ->orderBy('asl_users.first_name', $order)
                ->orderBy('asl_users.email', $order))
            ->orderColumn('creator_email', fn ($q, $order) => $q->orderBy('asl_users.email', $order))
            ->orderColumn('items_count', fn ($q, $order) => $q->orderBy('items_count', $order))
            ->orderColumn('published_at_formatted', fn ($q, $order) => $q->orderBy('asl_daily_menus.published_at', $order))
            ->orderColumn('created_at_formatted', fn ($q, $order) => $q->orderBy('asl_daily_menus.created_at', $order))
            ->orderColumn('status', fn ($q, $order) => $q->orderBy('asl_daily_menus.status', $order))
            ->toJson();
    }
}
