<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyMenu\DuplicateDailyMenuRequest;
use App\Http\Requests\DailyMenu\StoreDailyMenuRequest;
use App\Http\Requests\DailyMenu\UpdateDailyMenuRequest;
use App\Models\DailyMenu;
use App\Services\DailyMenuAdminService;
use App\Services\DailyMenuCacheService;
use App\Services\DailyMenuService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDailyMenuController extends Controller
{
    public function __construct(
        private readonly DailyMenuAdminService $dailyMenuAdminService,
        private readonly DailyMenuService $dailyMenuService,
        private readonly DailyMenuCacheService $dailyMenuCache
    ) {}

    public function index(Request $request): mixed
    {
        return $this->dailyMenuAdminService->getDataTables($request);
    }

    public function statsSummary(Request $request): JsonResponse
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $horizon = (int) $request->query('missing_horizon_days', 14);
        if ($horizon < 1 || $horizon > 366) {
            return $this->apiError('missing_horizon_days must be between 1 and 366.', 422);
        }

        try {
            $fromCarbon = $from !== null && $from !== '' ? Carbon::parse((string) $from)->startOfDay() : null;
            $toCarbon = $to !== null && $to !== '' ? Carbon::parse((string) $to)->endOfDay() : null;
        } catch (\Throwable) {
            return $this->apiError('Invalid from or to date.', 422);
        }

        $fingerprint = md5(json_encode([
            $fromCarbon?->toDateString(),
            $toCarbon?->toDateString(),
            $horizon,
        ], JSON_THROW_ON_ERROR));

        $data = $this->dailyMenuCache->rememberStatsSummary(
            $fingerprint,
            fn () => $this->dailyMenuService->statsSummary($fromCarbon, $toCarbon, $horizon)
        );

        return $this->apiSuccess($data, 'Daily menu stats summary.');
    }

    public function show(DailyMenu $dailyMenu): JsonResponse
    {
        $data = $this->dailyMenuCache->rememberAdminShow(
            (string) $dailyMenu->id,
            function () use ($dailyMenu): array {
                $fresh = DailyMenu::query()->find($dailyMenu->id);
                if ($fresh === null) {
                    return [];
                }

                return $this->dailyMenuService->formatAdminDetail($fresh);
            }
        );

        if ($data === []) {
            return $this->apiError('Daily menu not found.', 404);
        }

        return $this->apiSuccess($data, 'Daily menu fetched successfully.');
    }

    public function store(StoreDailyMenuRequest $request): JsonResponse
    {
        $menu = $this->dailyMenuService->create($request->user(), $request->validated());

        return $this->apiSuccess(
            $this->dailyMenuService->formatAdminDetail($menu),
            'Daily menu created successfully.',
            201
        );
    }

    public function update(UpdateDailyMenuRequest $request, DailyMenu $dailyMenu): JsonResponse
    {
        $menu = $this->dailyMenuService->update($dailyMenu, $request->validated());

        return $this->apiSuccess(
            $this->dailyMenuService->formatAdminDetail($menu),
            'Daily menu updated successfully.'
        );
    }

    public function publish(DailyMenu $dailyMenu): JsonResponse
    {
        $menu = $this->dailyMenuService->publish($dailyMenu);

        return $this->apiSuccess(
            $this->dailyMenuService->formatAdminDetail($menu),
            'Daily menu published successfully.'
        );
    }

    public function archive(DailyMenu $dailyMenu): JsonResponse
    {
        $menu = $this->dailyMenuService->archive($dailyMenu);

        return $this->apiSuccess(
            $this->dailyMenuService->formatAdminDetail($menu),
            'Daily menu archived successfully.'
        );
    }

    public function destroy(DailyMenu $dailyMenu): JsonResponse
    {
        try {
            $this->dailyMenuService->destroyIfDraft($dailyMenu);
        } catch (\RuntimeException $e) {
            return $this->apiError($e->getMessage(), 422);
        }

        return $this->apiSuccess(null, 'Daily menu deleted successfully.');
    }

    public function duplicate(DuplicateDailyMenuRequest $request, DailyMenu $dailyMenu): JsonResponse
    {
        $validated = $request->validated();
        $menu = $this->dailyMenuService->duplicateAsDraft(
            $request->user(),
            $dailyMenu,
            $validated
        );

        return $this->apiSuccess(
            $this->dailyMenuService->formatAdminDetail($menu),
            'Daily menu duplicated as a new draft.',
            201
        );
    }
}
