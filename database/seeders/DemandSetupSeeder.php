<?php

namespace Database\Seeders;

use App\Models\FoodItem;
use App\Models\FoodSchedule;
use App\Models\NonWorkingDate;
use App\Models\RationSetting;
use Illuminate\Database\Seeder;

class DemandSetupSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, unit: string, ration_grams: int}>
     */
    private const FOOD_ITEMS = [
        'bun' => ['name' => 'Bun', 'unit' => 'packets', 'ration_grams' => 120],
        'boiled_egg' => ['name' => 'Boiled Egg', 'unit' => 'pieces', 'ration_grams' => 60],
        'banana' => ['name' => 'Banana', 'unit' => 'pieces', 'ration_grams' => 100],
    ];

    /**
     * Exact dates from GPSFP Daily Demand, September 2026.
     *
     * @var array<string, array<int, string>>
     */
    private const SEPTEMBER_SCHEDULE = [
        '2026-09-01' => ['banana'],
        '2026-09-02' => ['bun', 'boiled_egg'],
        '2026-09-03' => ['bun', 'boiled_egg'],
        '2026-09-04' => ['bun', 'boiled_egg'],
        '2026-09-05' => ['bun'],
        '2026-09-06' => ['banana'],
        '2026-09-07' => ['bun', 'boiled_egg'],
        '2026-09-08' => ['bun', 'boiled_egg'],
        '2026-09-09' => ['bun', 'boiled_egg'],
        '2026-09-10' => ['bun'],
        '2026-09-11' => ['banana'],
        '2026-09-12' => ['bun', 'boiled_egg'],
        '2026-09-13' => ['bun', 'boiled_egg'],
        '2026-09-14' => ['bun', 'boiled_egg'],
        '2026-09-15' => ['bun'],
        '2026-09-16' => ['banana'],
        '2026-09-17' => ['bun', 'boiled_egg'],
        '2026-09-18' => ['bun', 'boiled_egg'],
        '2026-09-19' => ['bun'],
        '2026-09-20' => ['banana'],
        '2026-09-21' => ['bun', 'boiled_egg'],
    ];

    /**
     * The supplied schedule has no operating rows after 21 September.
     *
     * @var array<int, string>
     */
    private const SEPTEMBER_NON_WORKING_DATES = [
        '2026-09-22',
        '2026-09-23',
        '2026-09-24',
        '2026-09-25',
        '2026-09-26',
        '2026-09-27',
        '2026-09-28',
        '2026-09-29',
        '2026-09-30',
    ];

    public function run(): void
    {
        $itemsByKey = $this->seedFoodItems();
        $this->seedInitialRations($itemsByKey);
        $this->seedSeptemberSchedules($itemsByKey);
        $this->seedSeptemberNonWorkingDates();
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
                ],
            );
        }

        return $items;
    }

    /**
     * @param  array<string, FoodItem>  $itemsByKey
     */
    private function seedInitialRations(array $itemsByKey): void
    {
        foreach (self::FOOD_ITEMS as $key => $attributes) {
            $existingSetting = RationSetting::query()
                ->whereBelongsTo($itemsByKey[$key])
                ->whereDate('effective_start_date', '2026-09-01')
                ->first();

            if ($existingSetting === null) {
                RationSetting::query()->create([
                    'food_item_id' => $itemsByKey[$key]->id,
                    'effective_start_date' => '2026-09-01',
                    'ration_grams' => $attributes['ration_grams'],
                ]);
            }
        }
    }

    /**
     * @param  array<string, FoodItem>  $itemsByKey
     */
    private function seedSeptemberSchedules(array $itemsByKey): void
    {
        foreach (self::SEPTEMBER_SCHEDULE as $date => $itemKeys) {
            $schedule = FoodSchedule::query()->whereDate('date', $date)->first();

            if ($schedule !== null) {
                continue;
            }

            $schedule = FoodSchedule::query()->create(['date' => $date]);

            $schedule->items()->sync(array_map(
                fn (string $itemKey): int => $itemsByKey[$itemKey]->id,
                $itemKeys,
            ));
        }
    }

    private function seedSeptemberNonWorkingDates(): void
    {
        foreach (self::SEPTEMBER_NON_WORKING_DATES as $date) {
            $existingDate = NonWorkingDate::query()->whereDate('date', $date)->exists();

            if (! $existingDate) {
                NonWorkingDate::query()->create([
                    'date' => $date,
                    'reason' => 'No operating row in the supplied September 2026 schedule.',
                ]);
            }
        }
    }
}
