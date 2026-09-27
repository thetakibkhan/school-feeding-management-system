<?php

namespace App\Repositories;

use App\Models\Delivery;
use App\Models\FoodItem;
use App\Models\FoodSchedule;
use App\Models\NonWorkingDate;
use App\Models\School;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DailyDeliveryReportRepository
{
    public function isNonWorkingDate(string $date): bool
    {
        return NonWorkingDate::query()->whereDate('date', $date)->exists();
    }

    public function scheduleForDate(string $date): ?FoodSchedule
    {
        return FoodSchedule::query()
            ->with('items')
            ->whereDate('date', $date)
            ->first();
    }

    /** @return Collection<int, FoodItem> */
    public function foodItems(): Collection
    {
        return FoodItem::query()->orderBy('id')->get();
    }

    /** @return Collection<int, School> */
    public function schools(): Collection
    {
        return School::query()
            ->orderBy('school_code')
            ->get(['id', 'school_code', 'emis_code', 'name']);
    }

    /** @return Collection<int, Delivery> */
    public function deliveriesForDate(string $date): Collection
    {
        return Delivery::query()
            ->whereDate('date', $date)
            ->get()
            ->keyBy('school_id');
    }

    /** @return LengthAwarePaginator<int, Delivery> */
    public function paginateDeliveriesForCreatorOnDate(User $creator, string $date): LengthAwarePaginator
    {
        return Delivery::query()
            ->with('school:id,school_code,emis_code,name')
            ->whereBelongsTo($creator, 'creator')
            ->whereDate('date', $date)
            ->orderBy('school_id')
            ->paginate(10, ['*'], 'entries');
    }
}
