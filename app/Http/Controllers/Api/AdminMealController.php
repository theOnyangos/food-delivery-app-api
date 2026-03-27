<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MealAdminService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMealController extends Controller
{
    public function __construct(
        private readonly MealAdminService $mealAdminService
    ) {}

    public function index(Request $request): mixed
    {
        return $this->mealAdminService->getDataTables($request);
    }

    public function exportPdf(Request $request): Response
    {
        $rows = $this->mealAdminService->getPdfRows($request);
        $pdf = Pdf::loadView('exports.meals-pdf', [
            'rows' => $rows,
            'generatedAt' => now(),
        ]);

        return $pdf->download('meals-'.now()->format('Y-m-d-His').'.pdf');
    }
}
