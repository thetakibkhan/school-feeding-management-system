<?php

namespace App\Repositories;

use App\Models\Delivery;
use App\Models\DeliveryCorrectionHistory;
use App\Models\FoodSchedule;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DeliveryRepository
{
    /** @return LengthAwarePaginator<int, Delivery> */
    public function listForCreator(User $creator): LengthAwarePaginator
    {
        return Delivery::query()
            ->with('school')
            ->whereBelongsTo($creator, 'creator')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15);
    }

    public function scheduleForDate(string $date): ?FoodSchedule
    {
        return FoodSchedule::query()
            ->with('items')
            ->whereDate('date', $date)
            ->first();
    }

    public function hasEntryForSchoolDate(int $schoolId, string $date): bool
    {
        return Delivery::query()
            ->where('school_id', $schoolId)
            ->whereDate('date', $date)
            ->exists();
    }

    /** @param array<string, int|string> $attributes */
    public function create(array $attributes): Delivery
    {
        return Delivery::query()->create($attributes);
    }

    public function lockForUpdate(int $deliveryId): Delivery
    {
        return Delivery::query()->lockForUpdate()->findOrFail($deliveryId);
    }

    /** @param array<string, int|string> $attributes */
    public function save(Delivery $delivery, array $attributes): Delivery
    {
        $delivery->fill($attributes)->save();

        return $delivery->refresh();
    }

    /** @param array<string, int|string> $attributes */
    public function recordCorrection(array $attributes): DeliveryCorrectionHistory
    {
        return DeliveryCorrectionHistory::query()->create($attributes);
    }
}
