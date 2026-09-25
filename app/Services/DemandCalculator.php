<?php

namespace App\Services;

use App\Models\FoodItem;
use App\Models\FoodSchedule;
use App\Models\NonWorkingDate;
use App\Models\School;

class DemandCalculator
{
    public function __construct(private readonly StudentCountResolver $studentCounts) {}

    public function forSchoolDateItem(School $school, string $date, FoodItem $foodItem): DemandCalculation
    {
        if ($this->isNonWorkingDate($date) || ! $this->isScheduled($date, $foodItem)) {
            return new DemandCalculation(0, null, null);
        }

        $studentCount = $this->studentCounts->forDate($school, $date);
        if ($studentCount === null) {
            return new DemandCalculation(0, null, $foodItem->unit_weight_grams);
        }

        return new DemandCalculation(
            (int) round($studentCount * 0.90),
            $studentCount,
            $foodItem->unit_weight_grams,
        );
    }

    private function isNonWorkingDate(string $date): bool
    {
        return NonWorkingDate::query()->whereDate('date', $date)->exists();
    }

    private function isScheduled(string $date, FoodItem $foodItem): bool
    {
        return FoodSchedule::query()
            ->whereDate('date', $date)
            ->whereHas('items', fn ($query) => $query->whereKey($foodItem->id))
            ->exists();
    }
}
