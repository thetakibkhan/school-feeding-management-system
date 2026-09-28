<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\FoodItem;
use App\Models\FoodSchedule;
use App\Models\School;
use App\Models\SchoolMonthlyPlanningQuantity;
use Illuminate\Support\Carbon;

class FormTenReportService
{
    private const ITEM_COLUMNS = [
        'bun' => 'bun_quantity',
        'boiled_egg' => 'egg_quantity',
        'banana' => 'banana_quantity',
    ];

    private const MONTH_NAMES = [
        1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
        5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
        9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
    ];

    public function __construct(
        private readonly DemandCalculator $demandCalculator,
        private readonly StudentCountResolver $studentCounts,
        private readonly FormTenInvoiceNumberGenerator $invoiceNumbers,
    ) {}

    /** @return array<string, mixed> */
    public function forMonth(string $month): array
    {
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $items = FoodItem::query()->whereIn('key', array_keys(self::ITEM_COLUMNS))->get()->keyBy('key');
        $schedules = FoodSchedule::query()
            ->with('items')
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->whereHas('items')
            ->orderBy('date')
            ->get();
        $schools = School::query()->orderBy('school_code')->get();
        $deliveries = Delivery::query()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get(['school_id', 'date', 'bun_quantity', 'egg_quantity', 'banana_quantity', 'chalan_number']);
        $deliveriesBySchool = $deliveries->groupBy('school_id');
        $fixtureQuantities = SchoolMonthlyPlanningQuantity::query()
            ->where('month', $month)
            ->get()
            ->keyBy('school_id');

        $recordedDeliveryKeys = $deliveries->mapWithKeys(static fn (Delivery $delivery): array => [
            $delivery->date->toDateString().':'.$delivery->school_id => true,
        ]);
        $missingDeliveryCount = 0;
        $dailyDemand = array_fill_keys(array_keys(self::ITEM_COLUMNS), 0);
        $distributionDays = array_fill_keys(array_keys(self::ITEM_COLUMNS), 0);
        foreach ($schedules as $schedule) {
            $date = $schedule->date->toDateString();
            $effectiveCounts = $this->studentCounts->forSchoolsOnDate($schools->modelKeys(), $date);
            $missingDeliveryCount += count(array_filter(
                array_keys($effectiveCounts),
                static fn (int|string $schoolId): bool => ! isset($recordedDeliveryKeys[$date.':'.$schoolId]),
            ));

            foreach ($schedule->items as $scheduledItem) {
                if (! isset($dailyDemand[$scheduledItem->key])) {
                    continue;
                }

                $distributionDays[$scheduledItem->key]++;
                foreach ($effectiveCounts as $studentCount) {
                    $dailyDemand[$scheduledItem->key] += $this->demandCalculator
                        ->forResolvedInputs($studentCount, false, true, $scheduledItem)
                        ->quantity;
                }
            }
        }

        $deliveredQuantities = array_fill_keys(array_keys(self::ITEM_COLUMNS), 0);
        $fixtureSchoolCount = 0;
        $actualDeliverySchoolCount = 0;
        foreach ($schools as $school) {
            $schoolDeliveries = $deliveriesBySchool->get($school->id, collect());
            $fixtureQuantity = $fixtureQuantities->get($school->id);

            if ($schoolDeliveries->isEmpty() && $fixtureQuantity !== null) {
                $fixtureSchoolCount++;
                foreach (self::ITEM_COLUMNS as $key => $column) {
                    $deliveredQuantities[$key] += (int) $fixtureQuantity->{$column};
                }

                continue;
            }

            if ($schoolDeliveries->isNotEmpty()) {
                $actualDeliverySchoolCount++;
            }

            foreach ($schoolDeliveries as $delivery) {
                foreach (self::ITEM_COLUMNS as $key => $column) {
                    $deliveredQuantities[$key] += (int) $delivery->{$column};
                }
            }
        }

        $formItems = [];
        $grandTotalMills = 0;
        $warnings = [];
        foreach (self::ITEM_COLUMNS as $key => $column) {
            $item = $items->get($key);
            $price = $item?->unit_price;
            $lineTotalMills = $price === null ? null : $deliveredQuantities[$key] * $this->priceToMills((string) $price);
            if ($lineTotalMills !== null) {
                $grandTotalMills += $lineTotalMills;
            } else {
                $warnings[] = 'Source-backed unit price is missing for '.$key.'.';
            }

            $formItems[$key] = [
                'daily_demand' => $dailyDemand[$key],
                'distribution_days' => $distributionDays[$key],
                'delivered_quantity' => $deliveredQuantities[$key],
                'unit_price' => $price === null ? null : number_format((float) $price, 3, '.', ''),
                'line_total' => $lineTotalMills === null ? null : $this->formatTenths($lineTotalMills),
            ];
        }

        $period = $this->invoiceNumbers->ensureForMonth($month);

        if ($missingDeliveryCount > 0) {
            $warnings[] = $fixtureSchoolCount > 0
                ? 'Some scheduled deliveries have not yet been entered; supplied September demand quantities are shown for schools without recorded delivery entries.'
                : 'This report is generated from currently entered delivery records. Some scheduled deliveries have not yet been entered.';
        }
        if ($fixtureSchoolCount > 0) {
            $warnings[] = 'Supplied September demand quantities are shown for '.$fixtureSchoolCount.' schools without recorded monthly delivery entries. Actual monthly delivery records take precedence; these quantities do not create chalan records.';
        }
        foreach ([
            'invoice_date' => 'Invoice date',
            'contract_number' => 'Contract number',
            'bank_account_name' => 'Bank account name',
            'bank_account_number' => 'Bank account number',
            'bank_name' => 'Bank name',
            'bank_branch' => 'Bank branch',
            'bank_routing_number' => 'Bank routing number',
        ] as $field => $label) {
            if (blank($period->{$field})) {
                $warnings[] = $label.' has not been entered for this month.';
            }
        }
        if (blank($period->supplier_name)) {
            $warnings[] = 'Supplier/contractor name is not recorded for this month.';
        }
        if ($period->related_service_unit_price === null) {
            $warnings[] = 'Source-backed related service unit price is not recorded for this month.';
        }

        $grandTotal = (int) floor(($grandTotalMills + 500) / 1000);
        $chalanCount = $deliveries
            ->pluck('chalan_number')
            ->filter(static fn (?string $number): bool => filled($number))
            ->unique()
            ->count();
        $deliveriesWithoutChalan = $deliveries->filter(
            static fn (Delivery $delivery): bool => blank($delivery->chalan_number),
        )->count();
        if ($deliveriesWithoutChalan > 0) {
            $warnings[] = $deliveriesWithoutChalan.' entered delivery records have no chalan number.';
        }

        return [
            'month' => $month,
            'month_label' => self::MONTH_NAMES[(int) $start->format('n')].'-'.FormSevenReportService::bengaliDigits(substr($month, 2, 2)),
            'period' => $period,
            'supplier_name' => $period->supplier_name,
            'school_count' => $schools->count(),
            'fixture_school_count' => $fixtureSchoolCount,
            'actual_delivery_school_count' => $actualDeliverySchoolCount,
            'quantity_source' => match (true) {
                $fixtureSchoolCount > 0 && $actualDeliverySchoolCount > 0 => 'mixed_actual_and_assessment_fixture',
                $fixtureSchoolCount > 0 => 'assessment_fixture',
                $actualDeliverySchoolCount > 0 => 'actual_deliveries',
                default => 'missing',
            },
            'items' => $formItems,
            'related_service_unit_price' => $period->related_service_unit_price,
            'grand_total' => $grandTotal,
            'grand_total_formatted' => $this->formatIndianNumber($grandTotal),
            'grand_total_words' => $this->amountInWords($grandTotal),
            'chalan_count' => $chalanCount,
            'missing_delivery_count' => $missingDeliveryCount,
            'warnings' => $warnings,
        ];
    }

    private function formatTenths(int $milliAmount): string
    {
        $tenths = intdiv($milliAmount + 50, 100);

        return FormSevenReportService::bengaliDigits((string) intdiv($tenths, 10))
            .'.'.FormSevenReportService::bengaliDigits((string) ($tenths % 10));
    }

    private function priceToMills(string $price): int
    {
        [$whole, $fraction] = array_pad(explode('.', $price, 2), 2, '');

        return ((int) $whole * 1000) + (int) str_pad(substr($fraction, 0, 3), 3, '0');
    }

    private function formatIndianNumber(int $number): string
    {
        $formatted = (string) $number;
        if (strlen($formatted) > 3) {
            $lastThree = substr($formatted, -3);
            $leading = substr($formatted, 0, -3);
            $formatted = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $leading).','.$lastThree;
        }

        return FormSevenReportService::bengaliDigits($formatted);
    }

    private function amountInWords(int $amount): string
    {
        $words = [
            'শূন্য', 'এক', 'দুই', 'তিন', 'চার', 'পাঁচ', 'ছয়', 'সাত', 'আট', 'নয়',
            'দশ', 'এগারো', 'বারো', 'তেরো', 'চৌদ্দ', 'পনেরো', 'ষোল', 'সতেরো', 'আঠারো', 'ঊনিশ',
            'বিশ', 'একুশ', 'বাইশ', 'তেইশ', 'চব্বিশ', 'পঁচিশ', 'ছাব্বিশ', 'সাতাশ', 'আটাশ', 'ঊনত্রিশ',
            'ত্রিশ', 'একত্রিশ', 'বত্রিশ', 'তেত্রিশ', 'চৌত্রিশ', 'পঁয়ত্রিশ', 'ছত্রিশ', 'সাঁইত্রিশ', 'আটত্রিশ', 'ঊনচল্লিশ',
            'চল্লিশ', 'একচল্লিশ', 'বিয়াল্লিশ', 'তেতাল্লিশ', 'চুয়াল্লিশ', 'পঁয়তাল্লিশ', 'ছেচল্লিশ', 'সাতচল্লিশ', 'আটচল্লিশ', 'ঊনপঞ্চাশ',
            'পঞ্চাশ', 'একান্ন', 'বাহান্ন', 'তিপ্পান্ন', 'চুয়ান্ন', 'পঞ্চান্ন', 'ছাপ্পান্ন', 'সাতান্ন', 'আটান্ন', 'ঊনষাট',
            'ষাট', 'একষট্টি', 'বাষট্টি', 'তেষট্টি', 'চৌষট্টি', 'পঁয়ষট্টি', 'ছেষট্টি', 'সাতষট্টি', 'আটষট্টি', 'ঊনসত্তর',
            'সত্তর', 'একাত্তর', 'বাহাত্তর', 'তিয়াত্তর', 'চুয়াত্তর', 'পঁচাত্তর', 'ছিয়াত্তর', 'সাতাত্তর', 'আটাত্তর', 'ঊনআশি',
            'আশি', 'একাশি', 'বিরাশি', 'তিরাশি', 'চুরাশি', 'পঁচাশি', 'ছিয়াশি', 'সাতাশি', 'আটাশি', 'ঊননব্বই',
            'নব্বই', 'একানব্বই', 'বিরানব্বই', 'তিরানব্বই', 'চুরানব্বই', 'পঁচানব্বই', 'ছিয়ানব্বই', 'সাতানব্বই', 'আটানব্বই', 'নিরানব্বই',
        ];
        $parts = [];
        foreach ([10000000 => 'কোটি', 100000 => 'লক্ষ', 1000 => 'হাজার', 100 => 'শত'] as $divisor => $label) {
            $units = intdiv($amount, $divisor);
            if ($units > 0) {
                $parts[] = $this->underHundredWords($units, $words).' '.$label;
                $amount %= $divisor;
            }
        }
        if ($amount > 0 || $parts === []) {
            $parts[] = $this->underHundredWords($amount, $words);
        }

        return implode(' ', $parts).' টাকা মাত্র।';
    }

    /** @param list<string> $words */
    private function underHundredWords(int $number, array $words): string
    {
        if ($number <= 99) {
            return $words[$number];
        }

        return $words[intdiv($number, 100)].' শত'.($number % 100 > 0 ? ' '.$words[$number % 100] : '');
    }
}
