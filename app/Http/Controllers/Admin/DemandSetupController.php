<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\DemandSetupChangeBlocked;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFoodScheduleRequest;
use App\Http\Requests\StoreNonWorkingDateRequest;
use App\Http\Requests\StoreFoodItemSpecificationRequest;
use App\Models\FoodItem;
use App\Models\FoodSchedule;
use App\Models\NonWorkingDate;
use App\Services\DemandSetupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DemandSetupController extends Controller
{
    public function __construct(private readonly DemandSetupService $demandSetup) {}

    public function index(Request $request): View
    {
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString();

        return view('admin.demand-setup.index', [
            'foodItems' => FoodItem::query()->orderBy('id')->get(),
            'schedules' => FoodSchedule::query()
                ->with('items')
                ->when($from, fn ($query, string $date) => $query->whereDate('date', '>=', $date))
                ->when($to, fn ($query, string $date) => $query->whereDate('date', '<=', $date))
                ->orderBy('date')
                ->get(),
            'nonWorkingDates' => NonWorkingDate::query()
                ->when($from, fn ($query, string $date) => $query->whereDate('date', '>=', $date))
                ->when($to, fn ($query, string $date) => $query->whereDate('date', '<=', $date))
                ->orderBy('date')
                ->get(),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function updateItemSpecification(StoreFoodItemSpecificationRequest $request, FoodItem $foodItem): RedirectResponse
    {
        $data = $request->validated();
        $this->demandSetup->updateItemSpecification($foodItem, $data['unit_weight_grams']);

        return to_route('admin.demand-setup.index')->with('status', 'Item specification updated.');
    }

    public function storeSchedule(StoreFoodScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->demandSetup->createSchedule($data['date'], $data['food_item_ids']);

        return to_route('admin.demand-setup.index')->with('status', 'Working schedule added.');
    }

    public function updateSchedule(StoreFoodScheduleRequest $request, FoodSchedule $schedule): RedirectResponse
    {
        try {
            $data = $request->validated();
            $this->demandSetup->updateSchedule($schedule, $data['date'], $data['food_item_ids']);
        } catch (DemandSetupChangeBlocked $exception) {
            return to_route('admin.demand-setup.index')->with('error', $exception->getMessage());
        }

        return to_route('admin.demand-setup.index')->with('status', 'Working schedule updated.');
    }

    public function destroySchedule(FoodSchedule $schedule): RedirectResponse
    {
        try {
            $this->demandSetup->deleteSchedule($schedule);
        } catch (DemandSetupChangeBlocked $exception) {
            return to_route('admin.demand-setup.index')->with('error', $exception->getMessage());
        }

        return to_route('admin.demand-setup.index')->with('status', 'Working schedule deleted.');
    }

    public function storeNonWorkingDate(StoreNonWorkingDateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->demandSetup->createNonWorkingDate($data['date'], $data['reason'] ?? null);

        return to_route('admin.demand-setup.index')->with('status', 'Non-working date added.');
    }

    public function updateNonWorkingDate(StoreNonWorkingDateRequest $request, NonWorkingDate $nonWorkingDate): RedirectResponse
    {
        try {
            $data = $request->validated();
            $this->demandSetup->updateNonWorkingDate($nonWorkingDate, $data['date'], $data['reason'] ?? null);
        } catch (DemandSetupChangeBlocked $exception) {
            return to_route('admin.demand-setup.index')->with('error', $exception->getMessage());
        }

        return to_route('admin.demand-setup.index')->with('status', 'Non-working date updated.');
    }

    public function destroyNonWorkingDate(NonWorkingDate $nonWorkingDate): RedirectResponse
    {
        try {
            $this->demandSetup->deleteNonWorkingDate($nonWorkingDate);
        } catch (DemandSetupChangeBlocked $exception) {
            return to_route('admin.demand-setup.index')->with('error', $exception->getMessage());
        }

        return to_route('admin.demand-setup.index')->with('status', 'Non-working date removed.');
    }
}
