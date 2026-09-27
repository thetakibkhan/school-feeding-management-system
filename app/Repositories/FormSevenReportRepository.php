<?php

namespace App\Repositories;

use App\Models\Delivery;
use App\Models\FoodSchedule;
use App\Models\OfficialReportPeriod;
use App\Models\School;
use Illuminate\Database\Eloquent\Collection;

class FormSevenReportRepository
{
    /** @return Collection<int, School> */
    public function schools(): Collection
    {
        return School::query()
            ->orderBy('school_code')
            ->get(['id', 'school_code', 'emis_code', 'name']);
    }

    /** @return Collection<int, Delivery> */
    public function deliveriesForMonth(string $firstDate, string $lastDate): Collection
    {
        return Delivery::query()
            ->whereBetween('date', [$firstDate, $lastDate])
            ->get(['school_id', 'date', 'bun_quantity', 'egg_quantity', 'banana_quantity']);
    }

    /** @return list<string> */
    public function scheduledDatesForMonth(string $firstDate, string $lastDate): array
    {
        return FoodSchedule::query()
            ->whereBetween('date', [$firstDate, $lastDate])
            ->whereHas('items')
            ->orderBy('date')
            ->get(['date'])
            ->map(static fn (FoodSchedule $schedule): string => $schedule->date->toDateString())
            ->all();
    }

    public function supplierNameForMonth(string $month): ?string
    {
        return OfficialReportPeriod::query()
            ->where('month', $month)
            ->value('supplier_name');
    }
}
