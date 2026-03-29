<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PosSale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PosSaleAdminService
{
    public function getDataTables(Request $request): mixed
    {
        $query = PosSale::query()
            ->select('asl_pos_sales.*')
            ->join('asl_users', 'asl_pos_sales.sold_by', '=', 'asl_users.id');

        $dateInput = $request->input('filter_date');
        try {
            $day = $dateInput !== null && $dateInput !== ''
                ? Carbon::parse((string) $dateInput)->startOfDay()
                : Carbon::today();
        } catch (\Throwable) {
            $day = Carbon::today();
        }

        $query->whereDate('asl_pos_sales.created_at', $day);

        $nameTerm = trim((string) $request->input('sold_by_name', ''));
        if ($nameTerm !== '') {
            $like = '%'.$nameTerm.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('asl_users.first_name', 'like', $like)
                    ->orWhere('asl_users.last_name', 'like', $like)
                    ->orWhere('asl_users.email', 'like', $like);
            });
        }

        return DataTables::eloquent($query)
            ->addColumn('sold_by_name', function (PosSale $row): string {
                $row->loadMissing('soldByUser');
                $u = $row->soldByUser;
                if ($u === null) {
                    return '—';
                }
                $name = trim(($u->first_name ?? '').' '.($u->last_name ?? ''));

                return $name !== '' ? $name : ($u->email ?? '—');
            })
            ->addColumn('total_display', function (PosSale $row): string {
                $totals = $row->totals;
                $n = is_array($totals) ? (float) ($totals['total'] ?? 0) : 0.0;

                return number_format($n, 2, '.', '');
            })
            ->addColumn('created_at_formatted', fn (PosSale $row) => $row->created_at?->format('Y-m-d H:i') ?? '—')
            ->addColumn('order_type_label', function (PosSale $row): string {
                $raw = (string) $row->order_type;

                return ucfirst(str_replace('-', ' ', $raw));
            })
            ->orderColumn('receipt_number', fn ($q, $order) => $q->orderBy('asl_pos_sales.receipt_number', $order))
            ->orderColumn('total_display', function ($q, $order) {
                $driver = DB::connection()->getDriverName();
                if ($driver === 'sqlite') {
                    return $q->orderByRaw('CAST(json_extract(asl_pos_sales.totals, "$.total") AS REAL) '.$order);
                }

                return $q->orderByRaw('CAST(JSON_UNQUOTE(JSON_EXTRACT(asl_pos_sales.totals, "$.total")) AS DECIMAL(12,2)) '.$order);
            })
            ->orderColumn('sold_by_name', fn ($q, $order) => $q->orderBy('asl_users.last_name', $order)
                ->orderBy('asl_users.first_name', $order))
            ->orderColumn('order_type', fn ($q, $order) => $q->orderBy('asl_pos_sales.order_type', $order))
            ->orderColumn('order_type_label', fn ($q, $order) => $q->orderBy('asl_pos_sales.order_type', $order))
            ->orderColumn('customer_email', fn ($q, $order) => $q->orderBy('asl_pos_sales.customer_email', $order))
            ->orderColumn('created_at_formatted', fn ($q, $order) => $q->orderBy('asl_pos_sales.created_at', $order))
            ->toJson();
    }
}
