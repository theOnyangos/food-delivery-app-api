<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DeliveryZone;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DeliveryZoneAdminService
{
    public function getDataTables(Request $request): mixed
    {
        $query = DeliveryZone::query()
            ->select('asl_delivery_zones.*')
            ->latest('asl_delivery_zones.created_at');

        if ($request->filled('status')) {
            $query->where('asl_delivery_zones.status', $request->input('status'));
        }

        if ($request->filled('zip_code')) {
            $query->where('asl_delivery_zones.zip_code', 'like', '%'.$request->input('zip_code').'%');
        }

        return DataTables::eloquent($query)
            ->addColumn('delivery_fee_formatted', function (DeliveryZone $row): string {
                return number_format((float) $row->delivery_fee, 2, '.', '');
            })
            ->addColumn('minimum_order_display', function (DeliveryZone $row): string {
                return $row->minimum_order_amount !== null ? (string) $row->minimum_order_amount : '—';
            })
            ->addColumn('estimated_delivery_display', function (DeliveryZone $row): string {
                return $row->estimated_delivery_minutes !== null
                    ? (string) $row->estimated_delivery_minutes.' min'
                    : '—';
            })
            ->addColumn('is_serviceable_label', fn (DeliveryZone $row): string => $row->is_serviceable ? 'Yes' : 'No')
            ->addColumn('status_label', fn (DeliveryZone $row): string => $row->status === 'active' ? 'Active' : 'Inactive')
            ->addColumn('created_at_formatted', fn (DeliveryZone $row) => $row->created_at?->format('Y-m-d H:i') ?? '—')
            ->orderColumn('name', fn ($q, $order) => $q->orderBy('asl_delivery_zones.name', $order))
            ->orderColumn('zip_code', fn ($q, $order) => $q->orderBy('asl_delivery_zones.zip_code', $order))
            ->orderColumn('delivery_fee_formatted', fn ($q, $order) => $q->orderBy('asl_delivery_zones.delivery_fee', $order))
            ->orderColumn('status_label', fn ($q, $order) => $q->orderBy('asl_delivery_zones.status', $order))
            ->orderColumn('minimum_order_display', fn ($q, $order) => $q->orderBy('asl_delivery_zones.minimum_order_amount', $order))
            ->orderColumn('estimated_delivery_display', fn ($q, $order) => $q->orderBy('asl_delivery_zones.estimated_delivery_minutes', $order))
            ->orderColumn('is_serviceable_label', fn ($q, $order) => $q->orderBy('asl_delivery_zones.is_serviceable', $order === 'asc' ? 'asc' : 'desc'))
            ->orderColumn('created_at_formatted', fn ($q, $order) => $q->orderBy('asl_delivery_zones.created_at', $order))
            ->toJson();
    }
}
