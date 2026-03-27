<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DailyMenuCacheService;
use App\Services\DailyMenuService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyMenuController extends Controller
{
    public function __construct(
        private readonly DailyMenuService $dailyMenuService,
        private readonly DailyMenuCacheService $dailyMenuCache
    ) {}

    public function effective(Request $request): JsonResponse
    {
        $raw = $request->query('date');
        try {
            $dateYmd = $raw !== null && $raw !== ''
                ? Carbon::parse((string) $raw)->toDateString()
                : now()->toDateString();
        } catch (\Throwable) {
            return $this->apiError('Invalid date parameter.', 422);
        }

        $data = $this->dailyMenuCache->rememberEffective(
            $dateYmd,
            fn () => $this->dailyMenuService->resolveEffective($dateYmd)
        );

        return $this->apiSuccess($data, 'Effective daily menu resolved.');
    }
}
