<?php

namespace App\Services;

use App\Repositories\FormSevenReportRepository;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FormSevenReportService
{
    public function __construct(
        private readonly FormSevenReportRepository $reports,
        private readonly StudentCountResolver $studentCounts,
    ) {}

    private const MONTH_NAMES = [
        1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
        5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
        9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
    ];

    /** @return array{month: string, month_label: string, supplier_name: ?string, rows: list<array<string, mixed>>, totals: array<string, array{chalans: int, quantity: int}>, missing_delivery_count: int, fixture_school_count: int, missing_reasons: list<string>} */
    public function forMonth(string $month): array
    {
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $schools = $this->reports->schools();

        if ($schools->count() > 110) {
            throw ValidationException::withMessages([
                'month' => 'The supplied Form 7 template has 110 school rows; it cannot display additional schools without a verified continuation page.',
            ]);
        }

        $deliveries = $this->reports
            ->deliveriesForMonth($start->toDateString(), $end->toDateString())
            ->groupBy('school_id');
        $fixtureQuantities = $this->reports->assessmentFixtureQuantitiesForMonth($month)->keyBy('school_id');
        $recordedDeliveryKeys = $deliveries
            ->flatten(1)
            ->mapWithKeys(static fn ($delivery): array => [
                $delivery->date->toDateString().':'.$delivery->school_id => true,
            ]);
        $scheduledDates = $this->reports->scheduledDatesForMonth($start->toDateString(), $end->toDateString());
        $missingDeliveryCount = 0;

        foreach ($scheduledDates as $date) {
            $effectiveCounts = $this->studentCounts->forSchoolsOnDate($schools->modelKeys(), $date);
            $missingDeliveryCount += count(array_filter(
                array_keys($effectiveCounts),
                static fn (int|string $schoolId): bool => ! isset($recordedDeliveryKeys[$date.':'.$schoolId]),
            ));
        }

        $missingReasons = [];
        $supplierName = $this->reports->supplierNameForMonth($month);
        if (blank($supplierName)) {
            $missingReasons[] = 'Supplier/contractor name is not recorded for this month.';
        }
        if ($scheduledDates === []) {
            $missingReasons[] = 'No working-date food schedule is configured for this month.';
        }
        $totals = $this->emptyItems();
        $rows = [];
        $fixtureSchoolCount = 0;

        foreach ($schools as $school) {
            $schoolDeliveries = $deliveries->get($school->id, collect());
            $fixtureQuantity = $fixtureQuantities->get($school->id);
            $items = $this->emptyItems();
            $usesFixtureQuantity = $schoolDeliveries->isEmpty() && $fixtureQuantity !== null;

            if ($usesFixtureQuantity) {
                $fixtureSchoolCount++;
            }

            foreach ([
                'bun' => ['delivery' => 'bun_quantity', 'fixture' => 'bun_quantity'],
                'egg' => ['delivery' => 'egg_quantity', 'fixture' => 'egg_quantity'],
                'banana' => ['delivery' => 'banana_quantity', 'fixture' => 'banana_quantity'],
            ] as $item => $columns) {
                if ($usesFixtureQuantity) {
                    $items[$item]['quantity'] = (int) $fixtureQuantity->{$columns['fixture']};
                } else {
                    foreach ($schoolDeliveries as $delivery) {
                        $quantity = (int) $delivery->{$columns['delivery']};
                        if ($quantity > 0) {
                            $items[$item]['chalans']++;
                            $items[$item]['quantity'] += $quantity;
                        }
                    }
                }

                $totals[$item]['chalans'] += $items[$item]['chalans'];
                $totals[$item]['quantity'] += $items[$item]['quantity'];
            }

            $quantitySource = $usesFixtureQuantity
                ? 'assessment_fixture'
                : ($schoolDeliveries->isNotEmpty() ? 'actual_delivery' : 'missing');

            $rows[] = [
                'school' => $school,
                'quantity_source' => $quantitySource,
                ...$items,
            ];
        }

        $year = substr($month, 2, 2);
        $monthLabel = self::MONTH_NAMES[(int) $start->format('n')].'-'.self::bengaliDigits($year);

        return [
            'month' => $month,
            'month_label' => $monthLabel,
            'supplier_name' => $supplierName,
            'rows' => $rows,
            'totals' => $totals,
            'missing_delivery_count' => $missingDeliveryCount,
            'fixture_school_count' => $fixtureSchoolCount,
            'missing_reasons' => $missingReasons,
        ];
    }

    public static function bengaliDigits(int|string $value): string
    {
        return strtr((string) $value, [
            '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
            '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯',
        ]);
    }

    /** @return array<string, array{chalans: int, quantity: int}> */
    private function emptyItems(): array
    {
        return [
            'bun' => ['chalans' => 0, 'quantity' => 0],
            'egg' => ['chalans' => 0, 'quantity' => 0],
            'banana' => ['chalans' => 0, 'quantity' => 0],
        ];
    }
}
