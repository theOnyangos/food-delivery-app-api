<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MealCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class MealCategoryAdminService
{
    public function getDataTables(Request $request): mixed
    {
        $query = MealCategory::query()
            ->select('asl_meal_categories.*')
            ->withCount('meals')
            ->latest('asl_meal_categories.updated_at');

        return DataTables::eloquent($query)
            ->addColumn('description_excerpt', function (MealCategory $row): string {
                $d = $row->description ?? '';

                return $d !== '' ? Str::limit(strip_tags($d), 100) : '—';
            })
            ->addColumn('updated_at_formatted', fn (MealCategory $row) => $row->updated_at?->format('Y-m-d H:i') ?? '—')
            ->orderColumn('title', fn ($q, $order) => $q->orderBy('asl_meal_categories.title', $order))
            ->orderColumn('description_excerpt', fn ($q, $order) => $q->orderBy('asl_meal_categories.description', $order))
            ->orderColumn('icon', fn ($q, $order) => $q->orderBy('asl_meal_categories.icon', $order))
            ->orderColumn('meals_count', fn ($q, $order) => $q->orderBy('meals_count', $order))
            ->orderColumn('updated_at_formatted', fn ($q, $order) => $q->orderBy('asl_meal_categories.updated_at', $order))
            ->toJson();
    }
}
