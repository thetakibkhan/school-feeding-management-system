<?php

namespace Database\Seeders;

use App\Models\FoodItem;
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

    public function run(): void
    {
        $this->seedFoodItems();
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
}
