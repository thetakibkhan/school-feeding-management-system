<?php

namespace App\Http\Controllers\FieldStaff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeliveryRequest;
use App\Http\Requests\UpdateDeliveryRequest;
use App\Models\Delivery;
use App\Models\School;
use App\Repositories\DeliveryRepository;
use App\Services\DeliveryManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(Request $request, DeliveryRepository $deliveries): View
    {
        return view('field-staff.deliveries.index', [
            'deliveries' => $deliveries->listForCreator($request->user()),
        ]);
    }

    public function create(Request $request, DeliveryManagementService $deliveryService): View
    {
        $context = null;
        $date = $request->query('date');
        $schoolId = $request->query('school_id');

        if ($date !== null || $schoolId !== null) {
            $selection = Validator::make([
                'date' => $date,
                'school_id' => $schoolId,
            ], [
                'date' => ['required', 'date', 'before_or_equal:today'],
                'school_id' => ['required', 'integer', 'exists:schools,id'],
            ])->validate();

            $school = School::query()->findOrFail((int) $selection['school_id']);
            $context = $deliveryService->creationContext($school, $selection['date']);
        }

        return view('field-staff.deliveries.create', [
            'schools' => School::query()->orderBy('name')->get(),
            'foodItems' => $context['items'] ?? collect(),
            'schedule' => $context['schedule'] ?? null,
            'demand' => $context['demand'] ?? [],
            'selectedDate' => $date,
            'selectedSchoolId' => $schoolId,
            'quantityFields' => [
                'bun' => 'bun_quantity',
                'boiled_egg' => 'egg_quantity',
                'banana' => 'banana_quantity',
            ],
        ]);
    }

    public function store(StoreDeliveryRequest $request, DeliveryManagementService $deliveryService): RedirectResponse
    {
        $data = $request->validated();
        $deliveryService->create($data, $data['chalan_photo'], $request->user());

        return to_route('field-staff.deliveries.index')->with('status', 'Delivery entry saved.');
    }

    public function edit(Request $request, Delivery $delivery): View
    {
        abort_unless((int) $delivery->created_by_user_id === (int) $request->user()->getAuthIdentifier(), 404);

        return view('field-staff.deliveries.edit', [
            'delivery' => $delivery->load('school'),
        ]);
    }

    public function update(UpdateDeliveryRequest $request, Delivery $delivery, DeliveryManagementService $deliveryService): RedirectResponse
    {
        $data = $request->validated();
        $deliveryService->update($delivery, $data, $data['chalan_photo'] ?? null, $request->user());

        return to_route('field-staff.deliveries.index')->with('status', 'Delivery entry corrected.');
    }
}
