<?php

namespace App\Services;

use App\Models\Meal;
use App\Models\MealRecipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MealService
{
    public function __construct(
        private readonly MealCacheService $mealCache,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * @return Collection<int, Meal>
     */
    public function listManageableForUser(User $user, array $filters = []): Collection
    {
        $query = Meal::query()
            ->with($this->relations())
            ->orderByDesc('created_at');

        if (! $user->hasRole('Super Admin')) {
            $query->where('user_id', $user->id);
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        return $query->get();
    }

    /**
     * @return Collection<int, Meal>
     */
    public function listPublishedForUser(User $user, array $filters = []): Collection
    {
        $query = Meal::query()
            ->where('status', 'published')
            ->with($this->publicRelations($user))
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (string) $filters['category_id']);
        }

        if (! empty($filters['tag'])) {
            $query->whereJsonContains('tags', (string) $filters['tag']);
        }

        return $query->get();
    }

    public function createForOwner(User $user, array $payload): Meal
    {
        $meal = DB::transaction(function () use ($user, $payload): Meal {
            $meal = Meal::query()->create(array_merge(
                $this->extractMealAttributes($payload),
                ['user_id' => $user->id]
            ));

            $this->syncNestedData($meal, $payload);

            return $meal->fresh($this->relations()) ?? $meal->load($this->relations());
        });

        $this->mealCache->invalidate();
        $this->notifyAdminsAndPartners(
            type: 'meal_created',
            title: 'Meal Created',
            message: sprintf('Meal "%s" was created.', $meal->title),
            actor: $user,
            payload: [
                'meal_id' => $meal->id,
                'title' => $meal->title,
                'status' => $meal->status,
            ]
        );

        return $meal;
    }

    public function updateForOwner(User $user, Meal $meal, array $payload): Meal
    {
        $this->assertManageableBy($meal, $user);

        $updatedMeal = DB::transaction(function () use ($meal, $payload): Meal {
            $meal->fill($this->extractMealAttributes($payload))->save();
            $this->syncNestedData($meal, $payload);

            return $meal->fresh($this->relations()) ?? $meal->load($this->relations());
        });

        $this->mealCache->invalidate();
        $this->notifyAdminsAndPartners(
            type: 'meal_updated',
            title: 'Meal Updated',
            message: sprintf('Meal "%s" was updated.', $updatedMeal->title),
            actor: $user,
            payload: [
                'meal_id' => $updatedMeal->id,
                'title' => $updatedMeal->title,
                'status' => $updatedMeal->status,
            ]
        );

        return $updatedMeal;
    }

    public function deleteForOwner(User $user, Meal $meal): bool
    {
        $this->assertManageableBy($meal, $user);

        $deleted = (bool) $meal->delete();

        if ($deleted) {
            $this->mealCache->invalidate();
            $this->notifyAdminsAndPartners(
                type: 'meal_deleted',
                title: 'Meal Deleted',
                message: sprintf('Meal "%s" was deleted.', $meal->title),
                actor: $user,
                payload: [
                    'meal_id' => $meal->id,
                    'title' => $meal->title,
                    'status' => $meal->status,
                ]
            );
        }

        return $deleted;
    }

    public function showForOwner(User $user, Meal $meal): Meal
    {
        $this->assertManageableBy($meal, $user);

        return $meal->load($this->relations());
    }

    public function showPublishedForUser(User $user, Meal $meal): Meal
    {
        if ($meal->status !== 'published') {
            throw new \RuntimeException('Meal is not published.');
        }

        return $meal->load($this->publicRelations($user));
    }

    public function upsertNutritionForOwner(User $user, Meal $meal, ?array $nutrition): Meal
    {
        $this->assertManageableBy($meal, $user);

        $this->syncNutrition($meal, $nutrition, true);

        $this->mealCache->invalidate();

        return $meal->fresh($this->relations()) ?? $meal->load($this->relations());
    }

    /**
     * @param  array<int, array{title:string,description?:string|null}>  $allergens
     */
    public function syncAllergensForOwner(User $user, Meal $meal, array $allergens): Meal
    {
        $this->assertManageableBy($meal, $user);

        $this->syncAllergens($meal, $allergens);

        $this->mealCache->invalidate();

        return $meal->fresh($this->relations()) ?? $meal->load($this->relations());
    }

    /**
     * @param  array<int, array{meal_type?:string|null,metadata:array<int,array{name:string,value:string}>}>  $ingredients
     */
    public function syncIngredientsForOwner(User $user, Meal $meal, array $ingredients): Meal
    {
        $this->assertManageableBy($meal, $user);

        $this->syncIngredients($meal, $ingredients);

        $this->mealCache->invalidate();

        return $meal->fresh($this->relations()) ?? $meal->load($this->relations());
    }

    /**
     * @param  array<int, array{description?:string|null,status?:string,is_pro_only?:bool,steps?:array<int,array{title:string,description?:string|null,images?:array<int,string>|null,position?:int}>}>  $recipes
     */
    public function syncRecipesForOwner(User $user, Meal $meal, array $recipes): Meal
    {
        $this->assertManageableBy($meal, $user);

        $this->syncRecipes($meal, $recipes);

        $this->mealCache->invalidate();

        return $meal->fresh($this->relations()) ?? $meal->load($this->relations());
    }

    /**
     * @param  array<int, array{title:string,description?:string|null,video_url?:string|null}>  $tutorials
     */
    public function syncTutorialsForOwner(User $user, Meal $meal, array $tutorials): Meal
    {
        $this->assertManageableBy($meal, $user);

        $this->syncTutorials($meal, $tutorials);

        $this->mealCache->invalidate();

        return $meal->fresh($this->relations()) ?? $meal->load($this->relations());
    }

    private function extractMealAttributes(array $payload): array
    {
        $attributes = Arr::only($payload, [
            'category_id',
            'title',
            'excerpt',
            'description',
            'thumbnail_image',
            'images',
            'cooking_time',
            'servings',
            'calories',
            'status',
            'tags',
            'published_at',
        ]);

        if (($attributes['status'] ?? null) === 'published' && empty($attributes['published_at'])) {
            $attributes['published_at'] = now();
        }

        if (($attributes['status'] ?? null) !== null && $attributes['status'] !== 'published') {
            $attributes['published_at'] = null;
        }

        return $attributes;
    }

    private function syncNestedData(Meal $meal, array $payload): void
    {
        if (array_key_exists('nutrition', $payload)) {
            $this->syncNutrition($meal, $payload['nutrition'], true);
        }

        if (array_key_exists('allergens', $payload)) {
            $this->syncAllergens($meal, $payload['allergens']);
        }

        if (array_key_exists('ingredients', $payload)) {
            $this->syncIngredients($meal, $payload['ingredients']);
        }

        if (array_key_exists('tutorials', $payload)) {
            $this->syncTutorials($meal, $payload['tutorials']);
        }

        if (array_key_exists('recipes', $payload)) {
            $this->syncRecipes($meal, $payload['recipes']);
        }
    }

    private function syncNutrition(Meal $meal, ?array $nutrition, bool $allowDelete = false): void
    {
        if ($nutrition === null) {
            if ($allowDelete) {
                $meal->nutrition()->delete();
            }

            return;
        }

        $meal->nutrition()->updateOrCreate(
            ['meal_id' => $meal->id],
            [
                'fats' => $nutrition['fats'] ?? null,
                'protein' => $nutrition['protein'] ?? null,
                'carbs' => $nutrition['carbs'] ?? null,
                'metadata' => $nutrition['metadata'] ?? null,
            ]
        );
    }

    /**
     * @param  array<int, array{title:string,description?:string|null}>  $allergens
     */
    private function syncAllergens(Meal $meal, array $allergens): void
    {
        $meal->allergens()->delete();

        foreach ($allergens as $allergen) {
            $meal->allergens()->create([
                'title' => $allergen['title'],
                'description' => $allergen['description'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<int, array{meal_type?:string|null,metadata:array<int,array{name:string,value:string}>}>  $ingredients
     */
    private function syncIngredients(Meal $meal, array $ingredients): void
    {
        $meal->ingredients()->delete();

        foreach ($ingredients as $ingredient) {
            $meal->ingredients()->create([
                'meal_type' => $ingredient['meal_type'] ?? null,
                'metadata' => $ingredient['metadata'],
            ]);
        }
    }

    /**
     * @param  array<int, array{title:string,description?:string|null,video_url?:string|null}>  $tutorials
     */
    private function syncTutorials(Meal $meal, array $tutorials): void
    {
        $meal->tutorials()->delete();

        foreach ($tutorials as $tutorial) {
            $meal->tutorials()->create([
                'title' => $tutorial['title'],
                'description' => $tutorial['description'] ?? null,
                'video_url' => $tutorial['video_url'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<int, array{description?:string|null,status?:string,is_pro_only?:bool,steps?:array<int,array{title:string,description?:string|null,images?:array<int,string>|null,position?:int}>}>  $recipes
     */
    private function syncRecipes(Meal $meal, array $recipes): void
    {
        $meal->recipes()->delete();

        foreach ($recipes as $recipeData) {
            $recipe = $meal->recipes()->create([
                'description' => $recipeData['description'] ?? null,
                'status' => $recipeData['status'] ?? 'active',
                'is_pro_only' => (bool) ($recipeData['is_pro_only'] ?? false),
            ]);

            foreach ($recipeData['steps'] ?? [] as $index => $step) {
                $recipe->steps()->create([
                    'title' => $step['title'],
                    'description' => $step['description'] ?? null,
                    'images' => $step['images'] ?? null,
                    'position' => $step['position'] ?? ($index + 1),
                ]);
            }
        }
    }

    private function assertManageableBy(Meal $meal, User $user): void
    {
        if ($user->hasRole('Super Admin')) {
            return;
        }

        if ((string) $meal->user_id !== (string) $user->id) {
            throw new \RuntimeException('You are not allowed to manage this meal.');
        }
    }

    private function notifyAdminsAndPartners(string $type, string $title, string $message, User $actor, array $payload = []): void
    {
        $recipients = $this->notificationService
            ->getAdminAndPartnerUsers()
            ->reject(fn ($user): bool => (string) $user->id === (string) $actor->id)
            ->values();

        foreach ($recipients as $recipient) {
            $this->notificationService->create($recipient, $type, array_merge($payload, [
                'title' => $title,
                'message' => $message,
                'performed_by' => [
                    'id' => $actor->id,
                    'email' => $actor->email,
                    'name' => $actor->full_name,
                ],
            ]));
        }
    }

    /**
     * @return array<int, string|
     *     array<string, \Closure(\Illuminate\Database\Eloquent\Builder<MealRecipe>): void>>
     */
    private function publicRelations(User $user): array
    {
        $isProUser = $user->hasRole('Pro User');

        return [
            'category',
            'nutrition',
            'allergens',
            'ingredients',
            'tutorials',
            'recipes' => function ($query) use ($isProUser): void {
                $query->where('status', 'active')->orderByDesc('created_at');

                if (! $isProUser) {
                    $query->where('is_pro_only', false);
                }
            },
            'recipes.steps',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return [
            'owner',
            'category',
            'nutrition',
            'allergens',
            'ingredients',
            'tutorials',
            'recipes',
            'recipes.steps',
        ];
    }
}
