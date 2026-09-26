<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->deleteStaleSchedule('2026-09-04', ['boiled_egg', 'bun']);
        $this->removeEggFromStaleSeptemberTwentySeventh();
        $this->deleteStaleSchedule('2026-09-28', ['boiled_egg', 'bun']);
    }

    public function down(): void
    {
        // Restoring removed dates would reintroduce schedule data that the source does not support.
    }

    /** @param list<string> $expectedItemKeys */
    private function deleteStaleSchedule(string $date, array $expectedItemKeys): void
    {
        $schedule = DB::table('food_schedules')->whereDate('date', $date)->first(['id']);
        if ($schedule === null || DB::table('deliveries')->whereDate('date', $date)->exists()) {
            return;
        }

        if ($this->scheduledItemKeys((int) $schedule->id) !== $expectedItemKeys) {
            return;
        }

        DB::table('food_schedule_items')->where('food_schedule_id', $schedule->id)->delete();
        DB::table('food_schedules')->where('id', $schedule->id)->delete();
    }

    private function removeEggFromStaleSeptemberTwentySeventh(): void
    {
        $date = '2026-09-27';
        $schedule = DB::table('food_schedules')->whereDate('date', $date)->first(['id']);
        if ($schedule === null
            || DB::table('deliveries')->whereDate('date', $date)->exists()
            || $this->scheduledItemKeys((int) $schedule->id) !== ['boiled_egg', 'bun']) {
            return;
        }

        $eggId = DB::table('food_items')->where('key', 'boiled_egg')->value('id');
        if ($eggId !== null) {
            DB::table('food_schedule_items')
                ->where('food_schedule_id', $schedule->id)
                ->where('food_item_id', $eggId)
                ->delete();
        }
    }

    /** @return list<string> */
    private function scheduledItemKeys(int $scheduleId): array
    {
        return DB::table('food_schedule_items')
            ->join('food_items', 'food_items.id', '=', 'food_schedule_items.food_item_id')
            ->where('food_schedule_items.food_schedule_id', $scheduleId)
            ->orderBy('food_items.key')
            ->pluck('food_items.key')
            ->all();
    }
};
