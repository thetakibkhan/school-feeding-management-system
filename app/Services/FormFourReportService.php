<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\FoodSchedule;
use App\Models\School;
use Illuminate\Support\Carbon;

class FormFourReportService
{
    private const ITEMS = [
        'bun' => 'bun_quantity',
        'boiled_egg' => 'egg_quantity',
        'banana' => 'banana_quantity',
    ];

    private const MONTH_NAMES = [
        1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
        5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
        9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
    ];

    public function __construct(private readonly StudentCountResolver $studentCounts) {}

    /** @return array{month: string, month_label: string, school_count: int, missing_scheduled_entry_count: int, missing_chalan_field_count: int, warnings: list<string>, schools: list<array<string, mixed>>} */
    public function forMonth(string $month): array
    {
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $daysInMonth = $start->daysInMonth;
        $end = $start->copy()->endOfMonth();
        $schools = School::query()->orderBy('school_code')->get();
        $schoolIds = $schools->modelKeys();
        $schedules = FoodSchedule::query()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->whereHas('items')
            ->orderBy('date')
            ->get();
        $deliveries = Delivery::query()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get([
                'id', 'school_id', 'date', 'chalan_number', 'chalan_date',
                'bun_quantity', 'egg_quantity', 'banana_quantity',
            ]);
        $deliveryLookup = $deliveries->keyBy(
            static fn (Delivery $delivery): string => $delivery->date->toDateString().':'.$delivery->school_id,
        );

        $missingBySchool = array_fill_keys($schoolIds, 0);
        foreach ($schedules as $schedule) {
            $date = $schedule->date->toDateString();
            $effectiveCounts = $this->studentCounts->forSchoolsOnDate($schoolIds, $date);
            foreach (array_keys($effectiveCounts) as $schoolId) {
                if (! $deliveryLookup->has($date.':'.$schoolId)) {
                    $missingBySchool[$schoolId]++;
                }
            }
        }

        $missingDeliveryCount = array_sum($missingBySchool);
        $missingChalanFieldCount = $deliveries->filter(
            static fn (Delivery $delivery): bool => blank($delivery->chalan_number) || $delivery->chalan_date === null,
        )->count();

        $schoolPages = [];
        foreach ($schools as $school) {
            $dailyRows = [];
            $totals = array_fill_keys(array_keys(self::ITEMS), 0);

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $start->copy()->day($day)->toDateString();
                $delivery = $deliveryLookup->get($date.':'.$school->id);
                $quantities = [];
                foreach (self::ITEMS as $key => $column) {
                    $quantity = $delivery === null ? 0 : (int) $delivery->{$column};
                    $quantities[$key] = $quantity;
                    $totals[$key] += $quantity;
                }

                $dailyRows[] = [
                    'day' => $day,
                    'date' => $date,
                    'entry_recorded' => $delivery !== null,
                    'chalan_number' => $delivery === null ? '-' : (string) ($delivery->chalan_number ?? ''),
                    'chalan_date' => $delivery?->chalan_date?->toDateString(),
                    'quantities' => $quantities,
                ];
            }

            $schoolPages[] = [
                'school' => $school,
                'daily_rows' => $dailyRows,
                'totals' => $totals,
                'missing_scheduled_entry_count' => $missingBySchool[$school->id] ?? 0,
                'missing_chalan_field_count' => $deliveries
                    ->where('school_id', $school->id)
                    ->filter(static fn (Delivery $delivery): bool => blank($delivery->chalan_number) || $delivery->chalan_date === null)
                    ->count(),
            ];
        }

        $warnings = [];
        if ($missingDeliveryCount > 0) {
            $warnings[] = $missingDeliveryCount.' scheduled school/date delivery entries are not yet recorded. They appear as zero in the forms and are not stored or counted as challans.';
        }
        if ($missingChalanFieldCount > 0) {
            $warnings[] = $missingChalanFieldCount.' existing delivery records are missing a chalan number or chalan date.';
        }
        $warnings[] = 'The system records Bun, Boiled Egg, and Banana only; the other food columns in the official template are left blank.';

        return [
            'month' => $month,
            'month_label' => self::MONTH_NAMES[(int) $start->format('n')],
            'school_count' => $schools->count(),
            'missing_scheduled_entry_count' => $missingDeliveryCount,
            'missing_chalan_field_count' => $missingChalanFieldCount,
            'warnings' => $warnings,
            'schools' => $schoolPages,
        ];
    }
}
