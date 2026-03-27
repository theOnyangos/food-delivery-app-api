<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MealRecipe;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class MealRecipeAdminService
{
    public function getDataTables(Request $request): mixed
    {
        $query = MealRecipe::query()
            ->select('asl_meal_recipes.*')
            ->leftJoin('asl_meals', 'asl_meal_recipes.meal_id', '=', 'asl_meals.id')
            ->leftJoin('asl_meal_categories', 'asl_meals.category_id', '=', 'asl_meal_categories.id')
            ->leftJoin('asl_users', 'asl_meals.user_id', '=', 'asl_users.id')
            ->with(['meal.owner', 'meal.category'])
            ->withCount('steps')
            ->latest('asl_meal_recipes.updated_at');

        return DataTables::eloquent($query)
            ->addColumn('meal_thumbnail_image', fn (MealRecipe $row) => $row->meal?->thumbnail_image)
            ->addColumn('meal_title', fn (MealRecipe $row) => $row->meal?->title ?? '—')
            ->addColumn('partner_name', function (MealRecipe $row): string {
                $owner = $row->meal?->owner;

                return $owner !== null && $owner->full_name !== ''
                    ? $owner->full_name
                    : ($owner?->email ?? '—');
            })
            ->addColumn('partner_email', fn (MealRecipe $row) => $row->meal?->owner?->email ?? '—')
            ->addColumn('meal_category_name', fn (MealRecipe $row) => $row->meal?->category?->title ?? '—')
            ->addColumn('description_excerpt', function (MealRecipe $row): string {
                $d = $row->description ?? '';

                return $d !== '' ? Str::limit(strip_tags($d), 80) : '—';
            })
            ->addColumn('updated_at_formatted', fn (MealRecipe $row) => $row->updated_at?->format('Y-m-d H:i') ?? '—')
            ->orderColumn('meal_title', fn ($q, $order) => $q->orderBy('asl_meals.title', $order))
            ->orderColumn('meal_category_name', fn ($q, $order) => $q->orderBy('asl_meal_categories.title', $order))
            ->orderColumn('partner_name', fn ($q, $order) => $q->orderBy('asl_users.last_name', $order)
                ->orderBy('asl_users.first_name', $order)
                ->orderBy('asl_users.email', $order))
            ->orderColumn('description_excerpt', fn ($q, $order) => $q->orderBy('asl_meal_recipes.description', $order))
            ->orderColumn('steps_count', fn ($q, $order) => $q->orderBy('steps_count', $order))
            ->orderColumn('status', fn ($q, $order) => $q->orderBy('asl_meal_recipes.status', $order))
            ->orderColumn('is_pro_only', fn ($q, $order) => $q->orderBy('asl_meal_recipes.is_pro_only', $order))
            ->orderColumn('updated_at_formatted', fn ($q, $order) => $q->orderBy('asl_meal_recipes.updated_at', $order))
            ->toJson();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPdfRows(Request $request): array
    {
        return $this->recipeQueryForExport($request)
            ->get()
            ->map(function (MealRecipe $row): array {
                $owner = $row->meal?->owner;
                $d = $row->description ?? '';
                $descriptionExcerpt = $d !== '' ? Str::limit(strip_tags($d), 120) : '—';

                return [
                    'meal_title' => $row->meal?->title ?? '—',
                    'partner_name' => $owner !== null && $owner->full_name !== ''
                        ? $owner->full_name
                        : ($owner?->email ?? '—'),
                    'meal_category_name' => $row->meal?->category?->title ?? '—',
                    'description_excerpt' => $descriptionExcerpt,
                    'steps_count' => (int) ($row->steps_count ?? 0),
                    'status' => $row->status ?? '—',
                    'is_pro_only' => $row->is_pro_only ? 'Pro' : '—',
                    'updated_at_formatted' => $row->updated_at?->format('Y-m-d H:i') ?? '—',
                ];
            })
            ->all();
    }

    private function recipeQueryForExport(Request $request)
    {
        $query = MealRecipe::query()
            ->select('asl_meal_recipes.*')
            ->leftJoin('asl_meals', 'asl_meal_recipes.meal_id', '=', 'asl_meals.id')
            ->with(['meal.owner', 'meal.category'])
            ->withCount('steps')
            ->latest('asl_meal_recipes.updated_at');

        return $query;
    }
}
