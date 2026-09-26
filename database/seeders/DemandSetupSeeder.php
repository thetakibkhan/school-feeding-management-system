<?php

namespace Database\Seeders;

use App\Models\FoodItem;
use App\Models\FoodSchedule;
use App\Models\NonWorkingDate;
use Illuminate\Database\Seeder;

class DemandSetupSeeder extends Seeder
{
    /**
     * Distribution sequence counts; these are not calendar dates.
     *
     * @var array<string, int>
     */
    public const SOURCE_DISTRIBUTION_DAY_TOTALS = [
        'bun' => 16,
        'boiled_egg' => 12,
        'banana' => 5,
    ];

    /**
     * @var array<string, array{name: string, unit: string, unit_weight_grams: int}>
     */
    private const FOOD_ITEMS = [
        'bun' => ['name' => 'Bun', 'unit' => 'packets', 'unit_weight_grams' => 120],
        'boiled_egg' => ['name' => 'Boiled Egg', 'unit' => 'pieces', 'unit_weight_grams' => 60],
        'banana' => ['name' => 'Banana', 'unit' => 'pieces', 'unit_weight_grams' => 100],
    ];

    /**
     * Exact calendar dates and items transcribed from the September 2026
     * Daily Demand PDF. Dates absent from this source are not inferred as
     * holidays or working dates.
     *
     * @var array<string, array<int, string>>
     */
    private const SEPTEMBER_SCHEDULE = [
        '2026-09-01' => ['banana'],
        '2026-09-02' => ['bun', 'boiled_egg'],
        '2026-09-03' => ['bun', 'boiled_egg'],
        '2026-09-06' => ['bun', 'boiled_egg'],
        '2026-09-07' => ['bun'],
        '2026-09-08' => ['banana'],
        '2026-09-09' => ['bun', 'boiled_egg'],
        '2026-09-10' => ['bun', 'boiled_egg'],
        '2026-09-13' => ['bun', 'boiled_egg'],
        '2026-09-14' => ['bun'],
        '2026-09-15' => ['banana'],
        '2026-09-16' => ['bun', 'boiled_egg'],
        '2026-09-17' => ['bun', 'boiled_egg'],
        '2026-09-20' => ['bun', 'boiled_egg'],
        '2026-09-21' => ['bun'],
        '2026-09-22' => ['banana'],
        '2026-09-23' => ['bun', 'boiled_egg'],
        '2026-09-24' => ['bun', 'boiled_egg'],
        '2026-09-27' => ['bun'],
        '2026-09-29' => ['banana'],
        '2026-09-30' => ['bun', 'boiled_egg'],
    ];

    public function run(): void
    {
        $itemsByKey = $this->seedFoodItems();
        $this->seedSeptemberSchedules($itemsByKey);
    }

    /**
     * @return array<string, FoodItem>
     */
    private function seedFoodItems(): array
    {
        $items = [];

        foreach (self::FOOD_ITEMS as $key => $attributes) {
            $items[$key] = FoodItem::query()->firstOrCreate(
                ['key' => $key],
                [
                    'name' => $attributes['name'],
                    'unit' => $attributes['unit'],
                    'unit_weight_grams' => $attributes['unit_weight_grams'],
                ],
            );
        }

        return $items;
    }

    /**
     * @param  array<string, FoodItem>  $itemsByKey
     */
    private function seedSeptemberSchedules(array $itemsByKey): void
    {
        foreach (self::SEPTEMBER_SCHEDULE as $date => $itemKeys) {
            if (FoodSchedule::query()->whereDate('date', $date)->exists()
                || NonWorkingDate::query()->whereDate('date', $date)->exists()) {
                continue;
            }

            $schedule = FoodSchedule::query()->create(['date' => $date]);
            $schedule->items()->sync(array_map(
                fn (string $itemKey): int => $itemsByKey[$itemKey]->id,
                $itemKeys,
            ));
        }
    }
}
