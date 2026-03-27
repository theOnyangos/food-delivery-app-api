<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MealCategoryAdminService;
use Illuminate\Http\Request;

class AdminMealCategoryController extends Controller
{
    public function __construct(
        private readonly MealCategoryAdminService $mealCategoryAdminService
    ) {}

    public function index(Request $request): mixed
    {
        return $this->mealCategoryAdminService->getDataTables($request);
    }
}
