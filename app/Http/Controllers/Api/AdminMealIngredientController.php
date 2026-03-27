<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Meal\StoreAdminMealIngredientRequest;
use App\Http\Requests\Meal\UpdateAdminMealIngredientRequest;
use App\Models\MealIngredient;
use App\Services\MealIngredientService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMealIngredientController extends Controller
{
    public function __construct(
        private readonly MealIngredientService $mealIngredientService
    ) {}

    public function index(Request $request): mixed
    {
        return $this->mealIngredientService->getDataTables($request);
    }

    public function mealOptions(): JsonResponse
    {
        return $this->apiSuccess($this->mealIngredientService->listMealOptions(), 'Meal options fetched successfully.');
    }

    public function exportPdf(Request $request): Response
    {
        $rows = $this->mealIngredientService->getPdfRows($request);
        $pdf = Pdf::loadView('exports.meal-ingredients-pdf', [
            'rows' => $rows,
            'generatedAt' => now(),
        ]);

        return $pdf->download('meal-ingredients-'.now()->format('Y-m-d-His').'.pdf');
    }

    public function show(MealIngredient $mealIngredient): JsonResponse
    {
        return $this->apiSuccess(
            $this->mealIngredientService->showForAdmin($mealIngredient),
            'Meal ingredient fetched successfully.'
        );
    }

    public function store(StoreAdminMealIngredientRequest $request): JsonResponse
    {
        $ingredient = $this->mealIngredientService->createForAdmin($request->validated());

        return $this->apiSuccess(
            $this->mealIngredientService->showForAdmin($ingredient->fresh(['meal.owner', 'meal.category'])),
            'Meal ingredient created successfully.',
            201
        );
    }

    public function update(UpdateAdminMealIngredientRequest $request, MealIngredient $mealIngredient): JsonResponse
    {
        $updated = $this->mealIngredientService->updateForAdmin($mealIngredient, $request->validated());

        return $this->apiSuccess(
            $this->mealIngredientService->showForAdmin($updated),
            'Meal ingredient updated successfully.'
        );
    }

    public function destroy(MealIngredient $mealIngredient): JsonResponse
    {
        $this->mealIngredientService->deleteForAdmin($mealIngredient);

        return $this->apiSuccess(null, 'Meal ingredient deleted successfully.');
    }
}
