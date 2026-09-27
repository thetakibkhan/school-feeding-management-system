<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\FoodSchedule;
use App\Models\School;
use App\Models\SchoolMonthlyStockInput;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class StockReportService
{
    private const ITEMS = ['bun', 'egg', 'banana', 'biscuit', 'milk'];

    private const DELIVERY_COLUMNS = [
        'bun' => 'bun_quantity',
        'egg' => 'egg_quantity',
        'banana' => 'banana_quantity',
    ];

    private const MONTH_NAMES = [
        1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
        5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
        9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
    ];

    public function __construct(private readonly StudentCountResolver $studentCounts) {}

    /** @return array<string, mixed> */
    public function forMonth(string $month): array
    {
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $schools = School::query()->orderBy('school_code')->get();
        $scheduledDates = FoodSchedule::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['date'])->toBase()->map(fn (FoodSchedule $schedule): string => $schedule->date->toDateString());
        $inputs = SchoolMonthlyStockInput::query()->where('month', $month)->get()->keyBy('school_id');
        $deliveries = Delivery::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['school_id', 'date', 'bun_quantity', 'egg_quantity', 'banana_quantity'])
            ->groupBy('school_id');
        $studentCounts = $this->studentCounts->forSchoolsOnDate($schools->modelKeys(), $end->toDateString());

        $pages = [];
        $completeSchoolCount = 0;
        foreach ($schools as $school) {
            $input = $inputs->get($school->id);
            $schoolDeliveries = $deliveries->get($school->id, collect());
            $enteredDates = $schoolDeliveries->toBase()->map(fn (Delivery $delivery): string => $delivery->date->toDateString());
            $missingDeliveryCount = $scheduledDates->diff($enteredDates)->count();
            $items = [];
            $complete = $input !== null && $missingDeliveryCount === 0;
            foreach (self::ITEMS as $item) {
                $opening = $input?->{$item.'_opening'};
                $distributed = $input?->{$item.'_distributed'};
                $received = isset(self::DELIVERY_COLUMNS[$item])
                    ? ($missingDeliveryCount === 0 ? (int) $schoolDeliveries->sum(self::DELIVERY_COLUMNS[$item]) : null)
                    : $input?->{$item.'_received'};
                $recordedReceived = isset(self::DELIVERY_COLUMNS[$item])
                    ? ($schoolDeliveries->isNotEmpty()
                        ? (int) $schoolDeliveries->sum(self::DELIVERY_COLUMNS[$item])
                        : null)
                    : $received;
                $available = $opening === null || $received === null ? null : (int) $opening + (int) $received;
                $closing = $available === null || $distributed === null ? null : $available - (int) $distributed;
                if ($closing === null || $closing < 0) {
                    $complete = false;
                }
                $items[$item] = compact('opening', 'received', 'available', 'distributed', 'closing')
                    + ['recorded_received' => $recordedReceived];
            }

            if ($complete) {
                $completeSchoolCount++;
            }
            $pages[] = [
                'school' => $school,
                'input' => $input,
                'student_count' => $studentCounts[$school->id] ?? null,
                'missing_delivery_count' => $missingDeliveryCount,
                'items' => $items,
                'complete' => $complete,
            ];
        }

        $totals = [];
        foreach (self::ITEMS as $item) {
            $totals[$item] = [];
            foreach (['received', 'available', 'distributed', 'closing'] as $field) {
                $values = array_column(array_column($pages, 'items'), $item);
                $numbers = array_column($values, $field);
                $totals[$item][$field] = count($numbers) === $schools->count() && ! in_array(null, $numbers, true)
                    ? array_sum($numbers)
                    : null;
            }
        }

        return [
            'month' => $month,
            'month_label' => self::MONTH_NAMES[(int) $start->format('n')],
            'schools' => $pages,
            'school_count' => $schools->count(),
            'complete_school_count' => $completeSchoolCount,
            'totals' => $totals,
        ];
    }

    /** @param array<string, mixed> $data */
    public function saveSchoolInputs(School $school, string $month, array $data): SchoolMonthlyStockInput
    {
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $received = Delivery::query()
            ->where('school_id', $school->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['bun_quantity', 'egg_quantity', 'banana_quantity']);

        foreach (self::ITEMS as $item) {
            if (! array_key_exists($item.'_opening', $data) || ! array_key_exists($item.'_distributed', $data)) {
                continue;
            }
            $quantityReceived = isset(self::DELIVERY_COLUMNS[$item])
                ? (int) $received->sum(self::DELIVERY_COLUMNS[$item])
                : (int) ($data[$item.'_received'] ?? 0);
            if ($data[$item.'_distributed'] > $data[$item.'_opening'] + $quantityReceived) {
                throw ValidationException::withMessages([
                    $item.'_distributed' => 'Distributed quantity cannot exceed opening stock plus received quantity.',
                ]);
            }
        }

        return SchoolMonthlyStockInput::query()->updateOrCreate(
            ['school_id' => $school->id, 'month' => $month],
            $data,
        );
    }
}
