<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Meal;
use App\Models\MealIngredient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class MealIngredientService
{
    public function getDataTables(Request $request): mixed
    {
        $query = MealIngredient::query()
            ->select('asl_meal_ingredients.*')
            ->leftJoin('asl_meals', 'asl_meal_ingredients.meal_id', '=', 'asl_meals.id')
            ->leftJoin('asl_meal_categories', 'asl_meals.category_id', '=', 'asl_meal_categories.id')
            ->leftJoin('asl_users', 'asl_meals.user_id', '=', 'asl_users.id')
            ->with(['meal.owner', 'meal.category'])
            ->latest('asl_meal_ingredients.created_at');

        if ($request->filled('meal_id')) {
            $query->where('asl_meal_ingredients.meal_id', $request->input('meal_id'));
        }
        if ($request->filled('meal_type')) {
            $query->where('asl_meal_ingredients.meal_type', $request->input('meal_type'));
        }

        return DataTables::eloquent($query)
            ->addColumn('meal_thumbnail_image', fn (MealIngredient $row) => $row->meal?->thumbnail_image)
            ->addColumn('meal_title', fn (MealIngredient $row) => $row->meal?->title ?? '—')
            ->addColumn('partner_name', function (MealIngredient $row): string {
                $owner = $row->meal?->owner;

                return $owner !== null && $owner->full_name !== ''
                    ? $owner->full_name
                    : ($owner?->email ?? '—');
            })
            ->addColumn('partner_email', fn (MealIngredient $row) => $row->meal?->owner?->email ?? '—')
            ->addColumn('meal_category_name', fn (MealIngredient $row) => $row->meal?->category?->title ?? '—')
            ->addColumn('metadata_formatted', fn (MealIngredient $row) => $this->formatMetadata($row->metadata))
            ->addColumn('created_at_formatted', fn (MealIngredient $row) => $row->created_at?->format('Y-m-d H:i'))
            ->orderColumn('meal_title', fn ($q, $order) => $q->orderBy('asl_meals.title', $order))
            ->orderColumn('meal_category_name', fn ($q, $order) => $q->orderBy('asl_meal_categories.title', $order))
            ->orderColumn('meal_type', fn ($q, $order) => $q->orderBy('asl_meal_ingredients.meal_type', $order))
            ->orderColumn('partner_name', fn ($q, $order) => $q->orderBy('asl_users.last_name', $order)
                ->orderBy('asl_users.first_name', $order)
                ->orderBy('asl_users.email', $order))
            ->orderColumn('created_at_formatted', fn ($q, $order) => $q->orderBy('asl_meal_ingredients.created_at', $order))
            ->toJson();
    }

    /**
     * @return array<string, mixed>
     */
    public function showForAdmin(MealIngredient $mealIngredient): array
    {
        $mealIngredient->load(['meal.owner', 'meal.category']);

        $owner = $mealIngredient->meal?->owner;

        return [
            'id' => $mealIngredient->id,
            'meal_id' => $mealIngredient->meal_id,
            'meal_type' => $mealIngredient->meal_type,
            'metadata' => $mealIngredient->metadata,
            'metadata_formatted' => $this->formatMetadata($mealIngredient->metadata),
            'meal_title' => $mealIngredient->meal?->title ?? '—',
            'partner_name' => $owner !== null && $owner->full_name !== ''
                ? $owner->full_name
                : ($owner?->email ?? '—'),
            'partner_email' => $owner?->email ?? '—',
            'meal_category_name' => $mealIngredient->meal?->category?->title ?? '—',
            'created_at' => $mealIngredient->created_at?->toIso8601String(),
            'updated_at' => $mealIngredient->updated_at?->toIso8601String(),
            'created_at_formatted' => $mealIngredient->created_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * @param  array{meal_id: string, meal_type?: string|null, metadata: array<int, array{name: string, value: string}>}  $data
     */
    public function createForAdmin(array $data): MealIngredient
    {
        return MealIngredient::query()->create([
            'meal_id' => $data['meal_id'],
            'meal_type' => $data['meal_type'] ?? null,
            'metadata' => $data['metadata'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateForAdmin(MealIngredient $mealIngredient, array $data): MealIngredient
    {
        $payload = [];
        if (array_key_exists('meal_id', $data)) {
            $payload['meal_id'] = $data['meal_id'];
        }
        if (array_key_exists('meal_type', $data)) {
            $payload['meal_type'] = $data['meal_type'];
        }
        if (array_key_exists('metadata', $data)) {
            $payload['metadata'] = $data['metadata'];
        }
        if ($payload !== []) {
            $mealIngredient->update($payload);
        }

        return $mealIngredient->fresh(['meal.owner', 'meal.category']);
    }

    public function deleteForAdmin(MealIngredient $mealIngredient): bool
    {
        return (bool) $mealIngredient->delete();
    }

    /**
     * @return array<int, array{id: string, title: string, excerpt: string|null, thumbnail_image: string|null}>
     */
    public function listMealOptions(): array
    {
        return Meal::query()
            ->orderBy('title')
            ->get(['id', 'title', 'excerpt', 'thumbnail_image'])
            ->map(fn (Meal $m): array => [
                'id' => $m->id,
                'title' => $m->title,
                'excerpt' => $m->excerpt,
                'thumbnail_image' => $m->thumbnail_image,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPdfRows(Request $request): array
    {
        return $this->ingredientQueryForExport($request)
            ->get()
            ->map(function (MealIngredient $row): array {
                $owner = $row->meal?->owner;

                return [
                    'id' => $row->id,
                    'meal_title' => $row->meal?->title ?? '—',
                    'meal_type' => $row->meal_type,
                    'partner_name' => $owner !== null && $owner->full_name !== ''
                        ? $owner->full_name
                        : ($owner?->email ?? '—'),
                    'meal_category_name' => $row->meal?->category?->title ?? '—',
                    'metadata_formatted' => $this->formatMetadata($row->metadata),
                    'created_at_formatted' => $row->created_at?->format('Y-m-d H:i') ?? '—',
                ];
            })
            ->all();
    }

    /**
     * @return Builder<MealIngredient>
     */
    private function ingredientQueryForExport(Request $request)
    {
        $query = MealIngredient::query()
            ->select('asl_meal_ingredients.*')
            ->leftJoin('asl_meals', 'asl_meal_ingredients.meal_id', '=', 'asl_meals.id')
            ->with(['meal.owner', 'meal.category'])
            ->latest('asl_meal_ingredients.created_at');

        if ($request->filled('meal_id')) {
            $query->where('asl_meal_ingredients.meal_id', $request->input('meal_id'));
        }
        if ($request->filled('meal_type')) {
            $query->where('asl_meal_ingredients.meal_type', $request->input('meal_type'));
        }

        return $query;
    }

    /**
     * @param  array<int, array{name: string, value: string}>|null  $metadata
     */
    private function formatMetadata(?array $metadata): string
    {
        if ($metadata === null || $metadata === []) {
            return '—';
        }

        $parts = [];
        foreach ($metadata as $item) {
            if (is_array($item) && isset($item['name'], $item['value'])) {
                $parts[] = $item['name'].': '.$item['value'];
            }
        }

        return $parts !== [] ? implode('; ', $parts) : '—';
    }
}
