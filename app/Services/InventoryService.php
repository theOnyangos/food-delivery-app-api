<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryImportBatch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Support\InventoryConstants;
use App\Support\InventoryMovementType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class InventoryService
{
    public function computeStatus(InventoryItem $item): string
    {
        if ($item->expiration_date !== null) {
            $exp = Carbon::parse($item->expiration_date)->startOfDay();
            if ($exp->lt(Carbon::today()->startOfDay())) {
                return 'expired';
            }
        }

        $q = (float) (string) $item->quantity;
        if ($q <= 0) {
            return 'out_of_stock';
        }

        if ($item->low_stock_threshold !== null) {
            $th = (float) (string) $item->low_stock_threshold;
            if ($q <= $th) {
                return 'low_stock';
            }
        }

        return 'good';
    }

    /**
     * @return array{total_items: int, low_stock: int, expired: int, out_of_stock: int}
     */
    /**
     * @return list<array{id: string, sku: string|null, name: string, quantity: float, unit: string, image_url: string|null, storage_location: string|null}>
     */
    public function listItemOptions(): array
    {
        return InventoryItem::query()
            ->orderBy('name')
            ->get()
            ->map(function (InventoryItem $item): array {
                return [
                    'id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'quantity' => (float) (string) $item->quantity,
                    'unit' => $item->unit,
                    'image_url' => $item->image_url,
                    'storage_location' => $item->storage_location,
                ];
            })
            ->all();
    }

    public function summary(): array
    {
        $items = InventoryItem::query()->get();
        $total = $items->count();
        $low = 0;
        $expired = 0;
        $out = 0;

        foreach ($items as $item) {
            $s = $this->computeStatus($item);
            if ($s === 'low_stock') {
                $low++;
            }
            if ($s === 'expired') {
                $expired++;
            }
            if ($s === 'out_of_stock') {
                $out++;
            }
        }

        return [
            'total_items' => $total,
            'low_stock' => $low,
            'expired' => $expired,
            'out_of_stock' => $out,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createItem(array $data, ?string $userId): InventoryItem
    {
        return DB::transaction(function () use ($data, $userId): InventoryItem {
            $qty = (float) ($data['quantity'] ?? 0);
            $item = InventoryItem::query()->create([
                'sku' => isset($data['sku']) && $data['sku'] !== '' ? (string) $data['sku'] : null,
                'name' => (string) $data['name'],
                'image_url' => isset($data['image_url']) ? (string) $data['image_url'] : null,
                'quantity' => $qty,
                'unit' => (string) $data['unit'],
                'storage_location' => $data['storage_location'] ?? null,
                'storage_temperature_celsius' => $data['storage_temperature_celsius'] ?? null,
                'expiration_date' => $data['expiration_date'] ?? null,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? null,
            ]);

            if ($qty > 0) {
                $this->appendMovement(
                    $item,
                    InventoryMovementType::PURCHASE,
                    $qty,
                    $qty,
                    now(),
                    $userId,
                    null,
                    null,
                    'Initial stock'
                );
            }

            return $item->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateItem(InventoryItem $item, array $data, ?string $userId): InventoryItem
    {
        return DB::transaction(function () use ($item, $data, $userId): InventoryItem {
            $before = (float) (string) $item->quantity;

            if (array_key_exists('sku', $data)) {
                $item->sku = $data['sku'] !== null && $data['sku'] !== '' ? (string) $data['sku'] : null;
            }
            if (array_key_exists('name', $data)) {
                $item->name = (string) $data['name'];
            }
            if (array_key_exists('image_url', $data)) {
                $item->image_url = $data['image_url'] !== null && $data['image_url'] !== '' ? (string) $data['image_url'] : null;
            }
            if (array_key_exists('unit', $data)) {
                $item->unit = (string) $data['unit'];
            }
            if (array_key_exists('storage_location', $data)) {
                $item->storage_location = $data['storage_location'] !== null && $data['storage_location'] !== '' ? (string) $data['storage_location'] : null;
            }
            if (array_key_exists('storage_temperature_celsius', $data)) {
                $item->storage_temperature_celsius = $data['storage_temperature_celsius'] !== null && $data['storage_temperature_celsius'] !== ''
                    ? (float) $data['storage_temperature_celsius'] : null;
            }
            if (array_key_exists('expiration_date', $data)) {
                $item->expiration_date = $data['expiration_date'] !== null && $data['expiration_date'] !== ''
                    ? Carbon::parse((string) $data['expiration_date'])->format('Y-m-d') : null;
            }
            if (array_key_exists('low_stock_threshold', $data)) {
                $item->low_stock_threshold = $data['low_stock_threshold'] !== null && $data['low_stock_threshold'] !== ''
                    ? (float) $data['low_stock_threshold'] : null;
            }

            if (array_key_exists('quantity', $data)) {
                $after = (float) $data['quantity'];
                $delta = $after - $before;
                $item->quantity = $after;
                $item->save();

                if (abs($delta) > 0.00001) {
                    $this->appendMovement(
                        $item->fresh(),
                        InventoryMovementType::ADJUSTMENT,
                        $delta,
                        $after,
                        now(),
                        $userId,
                        null,
                        null,
                        'Quantity adjusted'
                    );
                }
            } else {
                $item->save();
            }

            return $item->fresh();
        });
    }

    public function deleteItem(InventoryItem $item): void
    {
        $item->delete();
    }

    /**
     * @param  list<array{inventory_item_id?: string, sku?: string, quantity_used: float|int|string}>  $lines
     */
    public function recordUsage(
        array $lines,
        Carbon $occurredAt,
        ?string $notes,
        string $userId
    ): array {
        $correlationId = (string) Str::uuid();

        return DB::transaction(function () use ($lines, $occurredAt, $notes, $userId, $correlationId): array {
            $processed = [];

            foreach ($lines as $line) {
                $used = (float) $line['quantity_used'];
                if ($used <= 0) {
                    throw ValidationException::withMessages([
                        'lines' => ['quantity_used must be positive for each line.'],
                    ]);
                }

                $item = null;
                if (! empty($line['inventory_item_id'])) {
                    $item = InventoryItem::query()->whereKey($line['inventory_item_id'])->lockForUpdate()->first();
                } elseif (! empty($line['sku'])) {
                    $item = InventoryItem::query()->where('sku', (string) $line['sku'])->lockForUpdate()->first();
                }

                if ($item === null) {
                    throw ValidationException::withMessages([
                        'lines' => ['Unknown inventory item in usage lines.'],
                    ]);
                }

                $current = (float) (string) $item->quantity;
                if ($used > $current) {
                    throw ValidationException::withMessages([
                        'lines' => ['Not enough stock for '.$item->name.' (SKU: '.($item->sku ?? '—').').'],
                    ]);
                }

                $after = round($current - $used, 4);
                $item->update(['quantity' => $after]);

                $this->appendMovement(
                    $item->fresh(),
                    InventoryMovementType::USAGE,
                    -$used,
                    $after,
                    $occurredAt,
                    $userId,
                    null,
                    $correlationId,
                    $notes
                );

                $processed[] = $item->id;
            }

            return ['correlation_id' => $correlationId, 'item_ids' => $processed];
        });
    }

    /**
     * @return array{imported: int, updated: int, errors: list<array{row: int, message: string}>}
     */
    public function importFromCsv(UploadedFile $file, string $userId): array
    {
        $path = $file->getRealPath();
        if ($path === false) {
            throw ValidationException::withMessages(['file' => ['Invalid upload.']]);
        }

        $rows = $this->parseCsvFile($path);
        if ($rows === []) {
            throw ValidationException::withMessages(['file' => ['CSV is empty.']]);
        }

        $header = array_shift($rows);
        if ($header === null || ! $this->headersMatch($header)) {
            throw ValidationException::withMessages([
                'file' => ['CSV headers must match the template exactly: '.implode(',', InventoryConstants::CSV_HEADERS)],
            ]);
        }

        $errors = [];
        $imported = 0;
        $updated = 0;

        $batch = InventoryImportBatch::query()->create([
            'file_name' => $file->getClientOriginalName(),
            'user_id' => $userId,
            'kind' => 'csv_import',
            'row_count' => 0,
            'errors_json' => null,
        ]);

        DB::transaction(function () use ($rows, $batch, $userId, &$errors, &$imported, &$updated): void {
            $rowNum = 1;
            foreach ($rows as $row) {
                $rowNum++;
                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $map = $this->mapRowToAssoc($row);
                $sku = trim((string) ($map['sku'] ?? ''));
                if ($sku === '') {
                    $errors[] = ['row' => $rowNum, 'message' => 'sku is required'];

                    continue;
                }

                try {
                    $unit = (string) ($map['unit'] ?? '');
                    if (! in_array($unit, InventoryConstants::UNITS, true)) {
                        throw new \InvalidArgumentException('Invalid unit: '.$unit);
                    }

                    $qty = (float) ($map['quantity'] ?? 0);
                    $name = trim((string) ($map['name'] ?? ''));
                    if ($name === '') {
                        throw new \InvalidArgumentException('name is required');
                    }

                    $loc = $map['storage_location'] ?? null;
                    $loc = $loc !== null && $loc !== '' ? (string) $loc : null;

                    $temp = $map['temperature_celsius'] ?? null;
                    $temp = $temp !== null && $temp !== '' ? (float) $temp : null;

                    $exp = $map['expiration_date'] ?? null;
                    $exp = $exp !== null && $exp !== '' ? Carbon::parse((string) $exp)->format('Y-m-d') : null;

                    $low = $map['low_stock_threshold'] ?? null;
                    $low = $low !== null && $low !== '' ? (float) $low : null;

                    $existing = InventoryItem::query()->where('sku', $sku)->lockForUpdate()->first();

                    if ($existing === null) {
                        $item = InventoryItem::query()->create([
                            'sku' => $sku,
                            'name' => $name,
                            'image_url' => null,
                            'quantity' => max(0, $qty),
                            'unit' => $unit,
                            'storage_location' => $loc,
                            'storage_temperature_celsius' => $temp,
                            'expiration_date' => $exp,
                            'low_stock_threshold' => $low,
                        ]);

                        $this->appendMovement(
                            $item,
                            InventoryMovementType::IMPORT_CREATE,
                            (float) (string) $item->quantity,
                            (float) (string) $item->quantity,
                            now(),
                            $userId,
                            $batch->id,
                            null,
                            'CSV import'
                        );
                        $imported++;
                    } else {
                        $before = (float) (string) $existing->quantity;
                        $existing->update([
                            'name' => $name,
                            'quantity' => max(0, $qty),
                            'unit' => $unit,
                            'storage_location' => $loc,
                            'storage_temperature_celsius' => $temp,
                            'expiration_date' => $exp,
                            'low_stock_threshold' => $low,
                        ]);
                        $existing->refresh();

                        $after = (float) (string) $existing->quantity;
                        $delta = $after - $before;
                        if (abs($delta) > 0.00001) {
                            $this->appendMovement(
                                $existing,
                                InventoryMovementType::IMPORT_UPDATE,
                                $delta,
                                $after,
                                now(),
                                $userId,
                                $batch->id,
                                null,
                                'CSV import update'
                            );
                        }
                        $updated++;
                    }
                } catch (\Throwable $e) {
                    $errors[] = ['row' => $rowNum, 'message' => $e->getMessage()];
                }
            }

            $batch->update([
                'row_count' => $imported + $updated,
                'errors_json' => $errors !== [] ? $errors : null,
            ]);
        });

        return [
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
            'batch_id' => $batch->id,
        ];
    }

    public function exportCsvStream(): StreamedResponse
    {
        $headers = InventoryConstants::CSV_HEADERS;

        return response()->streamDownload(function () use ($headers): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            InventoryItem::query()->orderBy('name')->chunk(200, function ($chunk) use ($out): void {
                foreach ($chunk as $item) {
                    /** @var InventoryItem $item */
                    fputcsv($out, [
                        $item->sku ?? '',
                        $item->name,
                        (string) $item->quantity,
                        $item->unit,
                        $item->storage_location ?? '',
                        $item->storage_temperature_celsius !== null ? (string) $item->storage_temperature_celsius : '',
                        $item->expiration_date?->format('Y-m-d') ?? '',
                        $item->low_stock_threshold !== null ? (string) $item->low_stock_threshold : '',
                    ]);
                }
            });
            fclose($out);
        }, 'inventory-export-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function templateCsvStream(): StreamedResponse
    {
        $headers = InventoryConstants::CSV_HEADERS;
        $example = ['SKU-001', 'Example tomatoes', '10', 'kg', 'Pantry', '4', '2026-12-31', '2'];

        return response()->streamDownload(function () use ($headers, $example): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $example);
            fclose($out);
        }, 'inventory-import-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function getDataTables(Request $request): mixed
    {
        $query = InventoryItem::query()->select('asl_inventory_items.*');

        if ($request->filled('location')) {
            $locTerm = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $request->input('location'))).'%';
            $query->where('asl_inventory_items.storage_location', 'like', $locTerm);
        }

        if ($request->filled('status')) {
            $status = (string) $request->input('status');
            $query->where(function ($q) use ($status): void {
                if ($status === 'expired') {
                    $q->whereNotNull('expiration_date')
                        ->whereDate('expiration_date', '<', Carbon::today());
                } elseif ($status === 'out_of_stock') {
                    $q->where('quantity', '<=', 0);
                } elseif ($status === 'low_stock') {
                    $q->where('quantity', '>', 0)
                        ->whereNotNull('low_stock_threshold')
                        ->whereColumn('quantity', '<=', 'low_stock_threshold')
                        ->where(function ($q2): void {
                            $q2->whereNull('expiration_date')
                                ->orWhereDate('expiration_date', '>=', Carbon::today());
                        });
                } elseif ($status === 'good') {
                    $q->where('quantity', '>', 0)
                        ->where(function ($q2): void {
                            $q2->whereNull('low_stock_threshold')
                                ->orWhereColumn('quantity', '>', 'low_stock_threshold');
                        })
                        ->where(function ($q2): void {
                            $q2->whereNull('expiration_date')
                                ->orWhereDate('expiration_date', '>=', Carbon::today());
                        });
                }
            });
        }

        return DataTables::eloquent($query)
            ->filter(function ($q) use ($request): void {
                $keyword = data_get($request->input('search'), 'value');
                if (! is_string($keyword) || trim($keyword) === '') {
                    return;
                }
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($keyword)).'%';
                $q->where(function ($sub) use ($term): void {
                    $sub->where('asl_inventory_items.name', 'like', $term)
                        ->orWhere('asl_inventory_items.sku', 'like', $term);
                });
            })
            ->addColumn('status_label', fn (InventoryItem $row) => $this->computeStatus($row))
            ->addColumn('quantity_display', function (InventoryItem $row): string {
                return trim((string) $row->quantity).' '.$row->unit;
            })
            ->addColumn('updated_at_formatted', fn (InventoryItem $row) => $row->updated_at?->format('M j, Y, H:i') ?? '—')
            ->addColumn('temperature_display', fn (InventoryItem $row) => $row->storage_temperature_celsius !== null
                ? (string) $row->storage_temperature_celsius.'°C'
                : '—')
            ->orderColumn('name', fn ($q, $order) => $q->orderBy('asl_inventory_items.name', $order))
            ->orderColumn('quantity', fn ($q, $order) => $q->orderBy('asl_inventory_items.quantity', $order))
            ->orderColumn('updated_at_formatted', fn ($q, $order) => $q->orderBy('asl_inventory_items.updated_at', $order))
            ->toJson();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listMovementsForItem(InventoryItem $item, int $limit = 100): array
    {
        $rows = $item->movements()
            ->with('createdByUser')
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $rows->map(function (InventoryMovement $m): array {
            return [
                'id' => $m->id,
                'type' => $m->type,
                'quantity_delta' => (float) (string) $m->quantity_delta,
                'quantity_after' => (float) (string) $m->quantity_after,
                'occurred_at' => $m->occurred_at?->toIso8601String(),
                'notes' => $m->notes,
                'created_by_name' => $m->createdByUser !== null
                    ? trim(($m->createdByUser->first_name ?? '').' '.($m->createdByUser->last_name ?? '')) ?: $m->createdByUser->email
                    : null,
            ];
        })->all();
    }

    /**
     * Aggregated analytics for a single item over a date range (inclusive calendar days in app timezone).
     *
     * @return array<string, mixed>
     */
    public function itemAnalytics(InventoryItem $item, Carbon $fromStart, Carbon $toEnd): array
    {
        $fromStart = $fromStart->copy()->startOfDay();
        $toEnd = $toEnd->copy()->endOfDay();

        $dateExpr = $this->sqlDateOccurredAtColumn();

        $dailyRows = DB::table('asl_inventory_movements')
            ->where('inventory_item_id', $item->id)
            ->where('type', InventoryMovementType::USAGE)
            ->whereBetween('occurred_at', [$fromStart, $toEnd])
            ->selectRaw($dateExpr.' as d, SUM(ABS(quantity_delta)) as usage_quantity')
            ->groupByRaw($dateExpr)
            ->orderBy('d')
            ->get();

        $dailyMap = [];
        foreach ($dailyRows as $row) {
            $dailyMap[(string) $row->d] = (float) (string) $row->usage_quantity;
        }

        $dailyUsage = [];
        $cursor = $fromStart->copy()->startOfDay();
        $endDay = $toEnd->copy()->startOfDay();
        while ($cursor->lte($endDay)) {
            $key = $cursor->format('Y-m-d');
            $qty = $dailyMap[$key] ?? 0.0;
            $dailyUsage[] = [
                'date' => $key,
                'label' => $cursor->format('M j'),
                'usage_quantity' => $qty,
            ];
            $cursor->addDay();
        }

        $cumulativeUsage = [];
        $running = 0.0;
        foreach ($dailyUsage as $row) {
            $running += $row['usage_quantity'];
            $cumulativeUsage[] = [
                'date' => $row['date'],
                'label' => $row['label'],
                'cumulative_quantity' => round($running, 4),
            ];
        }

        $usageByUserRows = DB::table('asl_inventory_movements')
            ->where('inventory_item_id', $item->id)
            ->where('type', InventoryMovementType::USAGE)
            ->whereBetween('occurred_at', [$fromStart, $toEnd])
            ->selectRaw('created_by, SUM(ABS(quantity_delta)) as usage_quantity')
            ->groupBy('created_by')
            ->get();

        $userIds = $usageByUserRows->pluck('created_by')->filter()->map(fn ($id) => (string) $id)->unique()->values()->all();
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        $usageByUser = [];
        foreach ($usageByUserRows as $row) {
            $uid = $row->created_by;
            $qty = (float) (string) $row->usage_quantity;
            if ($uid === null) {
                $usageByUser[] = [
                    'user_id' => null,
                    'user_name' => 'Unattributed',
                    'usage_quantity' => $qty,
                ];

                continue;
            }
            $u = $users->get((string) $uid);
            $usageByUser[] = [
                'user_id' => (string) $uid,
                'user_name' => $this->formatUserDisplayName($u),
                'usage_quantity' => $qty,
            ];
        }

        usort($usageByUser, fn (array $a, array $b): int => $b['usage_quantity'] <=> $a['usage_quantity']);

        $movementByTypeRows = DB::table('asl_inventory_movements')
            ->where('inventory_item_id', $item->id)
            ->whereBetween('occurred_at', [$fromStart, $toEnd])
            ->selectRaw('type, SUM(ABS(quantity_delta)) as volume')
            ->groupBy('type')
            ->orderBy('type')
            ->get();

        $movementVolumeByType = $movementByTypeRows->map(fn ($row): array => [
            'type' => (string) $row->type,
            'volume' => (float) (string) $row->volume,
        ])->all();

        $totalUsageQuantity = array_sum(array_column($dailyUsage, 'usage_quantity'));
        $usageEventCount = InventoryMovement::query()
            ->where('inventory_item_id', $item->id)
            ->where('type', InventoryMovementType::USAGE)
            ->whereBetween('occurred_at', [$fromStart, $toEnd])
            ->count();

        $distinctUsers = (int) (DB::table('asl_inventory_movements')
            ->where('inventory_item_id', $item->id)
            ->where('type', InventoryMovementType::USAGE)
            ->whereBetween('occurred_at', [$fromStart, $toEnd])
            ->whereNotNull('created_by')
            ->selectRaw('COUNT(DISTINCT created_by) as c')
            ->value('c') ?? 0);

        return [
            'item' => $this->formatItem($item),
            'range' => [
                'from' => $fromStart->toDateString(),
                'to' => $toEnd->copy()->startOfDay()->toDateString(),
            ],
            'summary' => [
                'total_usage_quantity' => round($totalUsageQuantity, 4),
                'usage_event_count' => $usageEventCount,
                'distinct_users_with_usage' => $distinctUsers,
            ],
            'daily_usage' => $dailyUsage,
            'cumulative_usage' => $cumulativeUsage,
            'usage_by_user' => $usageByUser,
            'movement_volume_by_type' => $movementVolumeByType,
        ];
    }

    private function sqlDateOccurredAtColumn(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d', occurred_at)",
            default => 'DATE(occurred_at)',
        };
    }

    private function formatUserDisplayName(?User $user): string
    {
        if ($user === null) {
            return 'Unknown';
        }

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name !== '' ? $name : (string) ($user->email ?? 'Unknown');
    }

    /**
     * @return array<string, mixed>
     */
    public function formatItem(InventoryItem $item): array
    {
        return [
            'id' => $item->id,
            'sku' => $item->sku,
            'name' => $item->name,
            'image_url' => $item->image_url,
            'quantity' => (float) (string) $item->quantity,
            'unit' => $item->unit,
            'storage_location' => $item->storage_location,
            'storage_temperature_celsius' => $item->storage_temperature_celsius !== null ? (float) (string) $item->storage_temperature_celsius : null,
            'expiration_date' => $item->expiration_date?->format('Y-m-d'),
            'low_stock_threshold' => $item->low_stock_threshold !== null ? (float) (string) $item->low_stock_threshold : null,
            'status' => $this->computeStatus($item),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }

    private function appendMovement(
        InventoryItem $item,
        string $type,
        float $delta,
        float $after,
        Carbon $occurredAt,
        ?string $userId,
        ?string $batchId,
        ?string $correlationId,
        ?string $notes
    ): void {
        InventoryMovement::query()->create([
            'inventory_item_id' => $item->id,
            'type' => $type,
            'quantity_delta' => $delta,
            'quantity_after' => $after,
            'occurred_at' => $occurredAt,
            'notes' => $notes,
            'created_by' => $userId,
            'inventory_import_batch_id' => $batchId,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * @param  list<string>  $header
     */
    private function headersMatch(array $header): bool
    {
        $normalized = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $expected = array_map(fn ($h) => strtolower($h), InventoryConstants::CSV_HEADERS);

        return $normalized === $expected;
    }

    /**
     * @return list<list<string>>
     */
    private function parseCsvFile(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }
        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = array_map(fn ($c) => (string) $c, $data);
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @param  list<string>  $row
     * @return array<string, string>
     */
    private function mapRowToAssoc(array $row): array
    {
        $headers = InventoryConstants::CSV_HEADERS;
        $out = [];
        foreach ($headers as $i => $key) {
            $out[$key] = $row[$i] ?? '';
        }

        return $out;
    }

    /**
     * @param  list<string>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
