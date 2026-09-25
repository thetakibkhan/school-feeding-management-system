<?php

namespace App\Services;

use App\Exceptions\DemandSetupChangeBlocked;
use App\Models\FoodSchedule;
use App\Models\NonWorkingDate;
use App\Models\RationSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DemandSetupService
{
    /**
     * @param  array<int, int>  $foodItemIds
     */
    public function createSchedule(string $date, array $foodItemIds): FoodSchedule
    {
        $this->ensureNoNonWorkingDate($date);

        return DB::transaction(function () use ($date, $foodItemIds): FoodSchedule {
            $schedule = FoodSchedule::query()->create(['date' => $date]);
            $schedule->items()->sync($foodItemIds);

            return $schedule;
        });
    }

    /**
     * @param  array<int, int>  $foodItemIds
     */
    public function updateSchedule(FoodSchedule $schedule, string $date, array $foodItemIds): void
    {
        $this->ensureNoDeliveryExists($schedule->date->toDateString());
        $this->ensureNoNonWorkingDate($date);

        DB::transaction(function () use ($schedule, $date, $foodItemIds): void {
            $schedule->update(['date' => $date]);
            $schedule->items()->sync($foodItemIds);
        });
    }

    public function deleteSchedule(FoodSchedule $schedule): void
    {
        $this->ensureNoDeliveryExists($schedule->date->toDateString());
        $schedule->delete();
    }

    public function createNonWorkingDate(string $date, ?string $reason): NonWorkingDate
    {
        $this->ensureNoActiveSchedule($date);

        return NonWorkingDate::query()->create([
            'date' => $date,
            'reason' => $reason,
        ]);
    }

    public function updateNonWorkingDate(NonWorkingDate $nonWorkingDate, string $date, ?string $reason): void
    {
        $this->ensureNoDeliveryExists($nonWorkingDate->date->toDateString());
        $this->ensureNoNonWorkingDate($date, $nonWorkingDate);
        $this->ensureNoActiveSchedule($date);

        $nonWorkingDate->update(['date' => $date, 'reason' => $reason]);
    }

    public function deleteNonWorkingDate(NonWorkingDate $nonWorkingDate): void
    {
        $this->ensureNoDeliveryExists($nonWorkingDate->date->toDateString());
        $nonWorkingDate->delete();
    }

    public function addRationSetting(int $foodItemId, int $rationGrams, string $effectiveStartDate): RationSetting
    {
        return RationSetting::query()->create([
            'food_item_id' => $foodItemId,
            'ration_grams' => $rationGrams,
            'effective_start_date' => $effectiveStartDate,
        ]);
    }

    private function ensureNoActiveSchedule(string $date): void
    {
        if (FoodSchedule::query()->whereDate('date', $date)->exists()) {
            throw ValidationException::withMessages([
                'date' => 'Remove the working schedule before marking this date as non-working.',
            ]);
        }
    }

    private function ensureNoNonWorkingDate(string $date, ?NonWorkingDate $except = null): void
    {
        $query = NonWorkingDate::query()->whereDate('date', $date);

        if ($except !== null) {
            $query->whereKeyNot($except->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'date' => 'A non-working date already exists for this date.',
            ]);
        }
    }

    private function ensureNoDeliveryExists(string $date): void
    {
        if (! $this->hasDeliveryOn($date)) {
            return;
        }

        throw new DemandSetupChangeBlocked(
            'This date already has delivery records, so its schedule or non-working status cannot be changed.',
        );
    }

    private function hasDeliveryOn(string $date): bool
    {
        if (! Schema::hasTable('deliveries')) {
            return false;
        }

        foreach (['date', 'delivery_date'] as $column) {
            if (Schema::hasColumn('deliveries', $column)) {
                return DB::table('deliveries')->whereDate($column, $date)->exists();
            }
        }

        return false;
    }
}
