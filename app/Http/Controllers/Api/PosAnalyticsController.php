<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PosAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosAnalyticsController extends Controller
{
    public function __construct(
        private readonly PosAnalyticsService $posAnalyticsService
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $range = $this->posAnalyticsService->parseDateRange($request);
        $includeDaily = filter_var($request->query('include_daily_series', false), FILTER_VALIDATE_BOOLEAN);

        $data = $this->posAnalyticsService->summary($range['from'], $range['to'], $includeDaily);

        return $this->apiSuccess($data, 'POS analytics summary.');
    }

    public function byMenu(Request $request): JsonResponse
    {
        $range = $this->posAnalyticsService->parseDateRange($request);
        $data = $this->posAnalyticsService->byMenu($range['from'], $range['to']);

        return $this->apiSuccess($data, 'POS analytics by daily menu.');
    }

    public function bySalesperson(Request $request): JsonResponse
    {
        $range = $this->posAnalyticsService->parseDateRange($request);
        $data = $this->posAnalyticsService->bySalesperson($range['from'], $range['to']);

        return $this->apiSuccess($data, 'POS analytics by salesperson.');
    }

    public function meals(Request $request): JsonResponse
    {
        $range = $this->posAnalyticsService->parseDateRange($request);
        $sort = strtolower((string) $request->query('sort', 'revenue'));
        if (! in_array($sort, ['revenue', 'quantity'], true)) {
            $sort = 'revenue';
        }
        $limit = (int) $request->query('limit', 20);
        $limit = max(1, min(100, $limit));

        $data = $this->posAnalyticsService->meals($range['from'], $range['to'], $sort, $limit);

        return $this->apiSuccess($data, 'POS analytics by meal.');
    }
}
