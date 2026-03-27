<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Meal;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class MealAdminService
{
    public function getDataTables(Request $request): mixed
    {
        $query = Meal::query()
            ->select('asl_meals.*')
            ->leftJoin('asl_meal_categories', 'asl_meals.category_id', '=', 'asl_meal_categories.id')
            ->leftJoin('asl_users', 'asl_meals.user_id', '=', 'asl_users.id')
            ->with(['owner', 'category'])
            ->latest('asl_meals.created_at');

        if ($request->filled('status')) {
            $query->where('asl_meals.status', $request->input('status'));
        }
        if ($request->filled('category_id')) {
            $query->where('asl_meals.category_id', $request->input('category_id'));
        }

        return DataTables::eloquent($query)
            ->addColumn('meal_title', fn (Meal $row) => $row->title ?? '—')
            ->addColumn('partner_name', function (Meal $row): string {
                $owner = $row->owner;

                return $owner !== null && $owner->full_name !== ''
                    ? $owner->full_name
                    : ($owner?->email ?? '—');
            })
            ->addColumn('partner_email', fn (Meal $row) => $row->owner?->email ?? '—')
            ->addColumn('meal_category_name', fn (Meal $row) => $row->category?->title ?? '—')
            ->addColumn('created_at_formatted', fn (Meal $row) => $row->created_at?->format('Y-m-d H:i'))
            ->addColumn('published_at_formatted', fn (Meal $row) => $row->published_at?->format('Y-m-d H:i') ?? '—')
            ->orderColumn('meal_title', fn ($q, $order) => $q->orderBy('asl_meals.title', $order))
            ->orderColumn('meal_category_name', fn ($q, $order) => $q->orderBy('asl_meal_categories.title', $order))
            ->orderColumn('status', fn ($q, $order) => $q->orderBy('asl_meals.status', $order))
            ->orderColumn('partner_name', fn ($q, $order) => $q->orderBy('asl_users.last_name', $order)
                ->orderBy('asl_users.first_name', $order)
                ->orderBy('asl_users.email', $order))
            ->orderColumn('calories', fn ($q, $order) => $q->orderBy('asl_meals.calories', $order))
            ->orderColumn('created_at_formatted', fn ($q, $order) => $q->orderBy('asl_meals.created_at', $order))
            ->orderColumn('published_at_formatted', fn ($q, $order) => $q->orderBy('asl_meals.published_at', $order))
            ->toJson();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPdfRows(Request $request): array
    {
        return $this->mealQueryForExport($request)
            ->get()
            ->map(function (Meal $row): array {
                $owner = $row->owner;

                return [
                    'meal_title' => $row->title ?? '—',
                    'status' => $row->status ?? '—',
                    'partner_name' => $owner !== null && $owner->full_name !== ''
                        ? $owner->full_name
                        : ($owner?->email ?? '—'),
                    'meal_category_name' => $row->category?->title ?? '—',
                    'calories' => $row->calories,
                    'published_at_formatted' => $row->published_at?->format('Y-m-d H:i') ?? '—',
                    'created_at_formatted' => $row->created_at?->format('Y-m-d H:i') ?? '—',
                ];
            })
            ->all();
    }

    private function mealQueryForExport(Request $request)
    {
        $query = Meal::query()
            ->select('asl_meals.*')
            ->leftJoin('asl_meal_categories', 'asl_meals.category_id', '=', 'asl_meal_categories.id')
            ->with(['owner', 'category'])
            ->latest('asl_meals.created_at');

        if ($request->filled('status')) {
            $query->where('asl_meals.status', $request->input('status'));
        }
        if ($request->filled('category_id')) {
            $query->where('asl_meals.category_id', $request->input('category_id'));
        }

        return $query;
    }
}
