<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The old date-wise seed translated distribution sequence numbers 1–21 to
     * September dates. This is retained here only to identify and remove those
     * exact generated rows from databases that already ran the old seeder.
     *
     * @var array<int, array<int, string>>
     */
    private const INCORRECT_SEPTEMBER_SEED = [
        ['banana'],
        ['bun', 'boiled_egg'],
        ['bun', 'boiled_egg'],
        ['bun', 'boiled_egg'],
        ['bun'],
        ['banana'],
        ['bun', 'boiled_egg'],
        ['bun', 'boiled_egg'],
        ['bun', 'boiled_egg'],
        ['bun'],
        ['banana'],
        ['bun', 'boiled_egg'],
        ['bun', 'boiled_egg'],
        ['bun', 'boiled_egg'],
        ['bun'],
        ['banana'],
        ['bun', 'boiled_egg'],
        ['bun', 'boiled_egg'],
        ['bun'],
        ['banana'],
        ['bun', 'boiled_egg'],
    ];

    public function up(): void
    {
        Schema::table('food_items', function (Blueprint $table): void {
            $table->unsignedInteger('unit_weight_grams')->default(1)->after('unit');
        });

        $defaultWeights = ['bun' => 120, 'boiled_egg' => 60, 'banana' => 100];
        $foodItems = DB::table('food_items')->get(['id', 'key']);

        foreach ($foodItems as $foodItem) {
            $latestConfiguredWeight = Schema::hasTable('ration_settings')
                ? DB::table('ration_settings')
                    ->where('food_item_id', $foodItem->id)
                    ->orderByDesc('effective_start_date')
                    ->orderByDesc('id')
                    ->value('ration_grams')
                : null;

            DB::table('food_items')
                ->where('id', $foodItem->id)
                ->update([
                    'unit_weight_grams' => $latestConfiguredWeight ?? ($defaultWeights[$foodItem->key] ?? 1),
                ]);
        }

        $this->removeIncorrectSeededSchedules();
        DB::table('non_working_dates')
            ->whereBetween('date', ['2026-09-22', '2026-09-30'])
            ->where('reason', 'No operating row in the supplied September 2026 schedule.')
            ->delete();

        Schema::dropIfExists('ration_settings');
    }

    public function down(): void
    {
        throw new \LogicException(
            'This migration consolidates ration history into item specifications and removes incorrect seeded dates. Restore a database backup instead of rolling it back.',
        );
    }

    private function removeIncorrectSeededSchedules(): void
    {
        $foodItemIds = DB::table('food_items')->pluck('id', 'key');

        foreach (self::INCORRECT_SEPTEMBER_SEED as $index => $expectedItemKeys) {
            $date = sprintf('2026-09-%02d', $index + 1);
            $scheduleId = DB::table('food_schedules')->whereDate('date', $date)->value('id');

            if ($scheduleId === null) {
                continue;
            }

            $actualItemIds = DB::table('food_schedule_items')
                ->where('food_schedule_id', $scheduleId)
                ->orderBy('food_item_id')
                ->pluck('food_item_id')
                ->all();
            $expectedItemIds = collect($expectedItemKeys)
                ->map(fn (string $key): ?int => $foodItemIds[$key] ?? null)
                ->filter()
                ->sort()
                ->values()
                ->all();

            if ($actualItemIds !== $expectedItemIds) {
                continue;
            }

            DB::table('food_schedule_items')->where('food_schedule_id', $scheduleId)->delete();
            DB::table('food_schedules')->where('id', $scheduleId)->delete();
        }
    }
};
