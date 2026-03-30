<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\InventoryItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds at least 20 sample food-inventory rows for local/staging demos.
 */
class InventorySampleSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();

        /** @var list<array{sku: string, name: string, quantity: float, unit: string, storage_location: string|null, storage_temperature_celsius: float|null, expiration_date: string|null, low_stock_threshold: float|null}> $rows */
        $rows = [
            ['sku' => 'INV-001', 'name' => 'Tomatoes (vine)', 'quantity' => 45.5, 'unit' => 'kg', 'storage_location' => 'Walk-in cooler A', 'storage_temperature_celsius' => 4.0, 'expiration_date' => $today->copy()->addDays(5)->toDateString(), 'low_stock_threshold' => 10.0],
            ['sku' => 'INV-002', 'name' => 'Chicken breast', 'quantity' => 28.0, 'unit' => 'kg', 'storage_location' => 'Freezer 1', 'storage_temperature_celsius' => -18.0, 'expiration_date' => $today->copy()->addMonths(2)->toDateString(), 'low_stock_threshold' => 8.0],
            ['sku' => 'INV-003', 'name' => 'Eggs (large)', 'quantity' => 120.0, 'unit' => 'pcs', 'storage_location' => 'Dry storage', 'storage_temperature_celsius' => 12.0, 'expiration_date' => $today->copy()->addDays(14)->toDateString(), 'low_stock_threshold' => 24.0],
            ['sku' => 'INV-004', 'name' => 'Pasta penne', 'quantity' => 18.0, 'unit' => 'kg', 'storage_location' => 'Pantry shelf 2', 'storage_temperature_celsius' => 20.0, 'expiration_date' => $today->copy()->addYear()->toDateString(), 'low_stock_threshold' => 5.0],
            ['sku' => 'INV-005', 'name' => 'Olive oil extra virgin', 'quantity' => 12.0, 'unit' => 'L', 'storage_location' => 'Pantry shelf 1', 'storage_temperature_celsius' => 18.0, 'expiration_date' => $today->copy()->addMonths(8)->toDateString(), 'low_stock_threshold' => 3.0],
            ['sku' => 'INV-006', 'name' => 'Whole milk', 'quantity' => 24.0, 'unit' => 'L', 'storage_location' => 'Walk-in cooler B', 'storage_temperature_celsius' => 3.5, 'expiration_date' => $today->copy()->addDays(6)->toDateString(), 'low_stock_threshold' => 6.0],
            ['sku' => 'INV-007', 'name' => 'Butter unsalted', 'quantity' => 8.0, 'unit' => 'kg', 'storage_location' => 'Walk-in cooler B', 'storage_temperature_celsius' => 4.0, 'expiration_date' => $today->copy()->addDays(30)->toDateString(), 'low_stock_threshold' => 2.0],
            ['sku' => 'INV-008', 'name' => 'Onions yellow', 'quantity' => 35.0, 'unit' => 'kg', 'storage_location' => 'Dry storage', 'storage_temperature_celsius' => 14.0, 'expiration_date' => $today->copy()->addWeeks(3)->toDateString(), 'low_stock_threshold' => 10.0],
            ['sku' => 'INV-009', 'name' => 'Garlic peeled', 'quantity' => 2.5, 'unit' => 'kg', 'storage_location' => 'Prep fridge', 'storage_temperature_celsius' => 2.0, 'expiration_date' => $today->copy()->addDays(7)->toDateString(), 'low_stock_threshold' => 0.5],
            ['sku' => 'INV-010', 'name' => 'Basmati rice', 'quantity' => 40.0, 'unit' => 'kg', 'storage_location' => 'Pantry shelf 3', 'storage_temperature_celsius' => 19.0, 'expiration_date' => $today->copy()->addMonths(10)->toDateString(), 'low_stock_threshold' => 10.0],
            ['sku' => 'INV-011', 'name' => 'Heavy cream', 'quantity' => 6.0, 'unit' => 'L', 'storage_location' => 'Walk-in cooler A', 'storage_temperature_celsius' => 4.0, 'expiration_date' => $today->copy()->addDays(10)->toDateString(), 'low_stock_threshold' => 2.0],
            ['sku' => 'INV-012', 'name' => 'Salmon fillet', 'quantity' => 15.0, 'unit' => 'kg', 'storage_location' => 'Freezer 2', 'storage_temperature_celsius' => -20.0, 'expiration_date' => $today->copy()->addMonth()->toDateString(), 'low_stock_threshold' => 4.0],
            ['sku' => 'INV-013', 'name' => 'Lemons', 'quantity' => 8.0, 'unit' => 'kg', 'storage_location' => 'Walk-in cooler A', 'storage_temperature_celsius' => 6.0, 'expiration_date' => $today->copy()->addDays(12)->toDateString(), 'low_stock_threshold' => 2.0],
            ['sku' => 'INV-014', 'name' => 'Salt kosher', 'quantity' => 10.0, 'unit' => 'kg', 'storage_location' => 'Pantry shelf 1', 'storage_temperature_celsius' => 20.0, 'expiration_date' => $today->copy()->addYears(2)->toDateString(), 'low_stock_threshold' => 2.0],
            ['sku' => 'INV-015', 'name' => 'Black pepper ground', 'quantity' => 1.2, 'unit' => 'kg', 'storage_location' => 'Spice rack', 'storage_temperature_celsius' => 20.0, 'expiration_date' => $today->copy()->addYear()->toDateString(), 'low_stock_threshold' => 0.3],
            ['sku' => 'INV-016', 'name' => 'Vanilla extract', 'quantity' => 500.0, 'unit' => 'ml', 'storage_location' => 'Bakery dry', 'storage_temperature_celsius' => 18.0, 'expiration_date' => $today->copy()->addMonths(18)->toDateString(), 'low_stock_threshold' => 100.0],
            ['sku' => 'INV-017', 'name' => 'Sugar granulated', 'quantity' => 25.0, 'unit' => 'kg', 'storage_location' => 'Bakery dry', 'storage_temperature_celsius' => 20.0, 'expiration_date' => $today->copy()->addMonths(12)->toDateString(), 'low_stock_threshold' => 5.0],
            ['sku' => 'INV-018', 'name' => 'Flour all-purpose', 'quantity' => 50.0, 'unit' => 'kg', 'storage_location' => 'Bakery dry', 'storage_temperature_celsius' => 19.0, 'expiration_date' => $today->copy()->addMonths(6)->toDateString(), 'low_stock_threshold' => 12.0],
            ['sku' => 'INV-019', 'name' => 'Yeast dry', 'quantity' => 800.0, 'unit' => 'g', 'storage_location' => 'Bakery dry', 'storage_temperature_celsius' => 18.0, 'expiration_date' => $today->copy()->addMonths(8)->toDateString(), 'low_stock_threshold' => 200.0],
            ['sku' => 'INV-020', 'name' => 'Mozzarella shredded', 'quantity' => 12.0, 'unit' => 'kg', 'storage_location' => 'Walk-in cooler A', 'storage_temperature_celsius' => 4.0, 'expiration_date' => $today->copy()->addDays(21)->toDateString(), 'low_stock_threshold' => 3.0],
            ['sku' => 'INV-021', 'name' => 'Canned tomatoes', 'quantity' => 48.0, 'unit' => 'pcs', 'storage_location' => 'Pantry shelf 2', 'storage_temperature_celsius' => 18.0, 'expiration_date' => $today->copy()->addMonths(14)->toDateString(), 'low_stock_threshold' => 12.0],
            ['sku' => 'INV-022', 'name' => 'Potatoes russet', 'quantity' => 60.0, 'unit' => 'kg', 'storage_location' => 'Dry storage', 'storage_temperature_celsius' => 10.0, 'expiration_date' => $today->copy()->addWeeks(6)->toDateString(), 'low_stock_threshold' => 15.0],
            ['sku' => 'INV-023', 'name' => 'Carrots', 'quantity' => 3.0, 'unit' => 'kg', 'storage_location' => 'Walk-in cooler A', 'storage_temperature_celsius' => 4.0, 'expiration_date' => $today->copy()->addDays(4)->toDateString(), 'low_stock_threshold' => 5.0],
            ['sku' => 'INV-024', 'name' => 'Spinach baby', 'quantity' => 4.0, 'unit' => 'kg', 'storage_location' => 'Walk-in cooler A', 'storage_temperature_celsius' => 2.0, 'expiration_date' => $today->copy()->subDay()->toDateString(), 'low_stock_threshold' => 2.0],
        ];

        foreach ($rows as $row) {
            InventoryItem::query()->updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'name' => $row['name'],
                    'image_url' => null,
                    'quantity' => $row['quantity'],
                    'unit' => $row['unit'],
                    'storage_location' => $row['storage_location'] ?? null,
                    'storage_temperature_celsius' => $row['storage_temperature_celsius'] ?? null,
                    'expiration_date' => isset($row['expiration_date']) ? Carbon::parse($row['expiration_date'])->format('Y-m-d') : null,
                    'low_stock_threshold' => $row['low_stock_threshold'] ?? null,
                ]
            );
        }
    }
}
