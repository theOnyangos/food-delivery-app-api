<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MealRecipe;
use App\Services\MealRecipeAdminService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMealRecipeController extends Controller
{
    public function __construct(
        private readonly MealRecipeAdminService $mealRecipeAdminService
    ) {}

    public function index(Request $request): mixed
    {
        return $this->mealRecipeAdminService->getDataTables($request);
    }

    public function exportPdf(Request $request): Response
    {
        $rows = $this->mealRecipeAdminService->getPdfRows($request);
        $pdf = Pdf::loadView('exports.meal-recipes-pdf', [
            'rows' => $rows,
            'generatedAt' => now(),
        ]);

        return $pdf->download('meal-recipes-'.now()->format('Y-m-d-His').'.pdf');
    }

    public function show(MealRecipe $mealRecipe): JsonResponse
    {
        $mealRecipe->load([
            'steps' => fn ($q) => $q->orderBy('position'),
            'meal:id,title',
        ]);

        return $this->apiSuccess(
            [
                'recipe' => $mealRecipe,
                'meal' => [
                    'id' => $mealRecipe->meal?->id,
                    'title' => $mealRecipe->meal?->title ?? '—',
                ],
            ],
            'Meal recipe fetched successfully.'
        );
    }
}
