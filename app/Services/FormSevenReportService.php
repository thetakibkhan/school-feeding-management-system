<?php

namespace App\Services;

use App\Repositories\FormSevenReportRepository;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FormSevenReportService
{
    public function __construct(private readonly FormSevenReportRepository $reports) {}

    private const MONTH_NAMES = [
        1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
        5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
        9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
    ];

    /** @return array{month: string, month_label: string, rows: list<array<string, mixed>>, totals: array<string, array{chalans: int, quantity: int}>} */
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

        $totals = $this->emptyItems();
        $rows = [];

        foreach ($schools as $school) {
            $schoolDeliveries = $deliveries->get($school->id, collect());
            $items = $this->emptyItems();

            foreach (['bun' => 'bun_quantity', 'egg' => 'egg_quantity', 'banana' => 'banana_quantity'] as $item => $column) {
                foreach ($schoolDeliveries as $delivery) {
                    $quantity = (int) $delivery->{$column};
                    if ($quantity > 0) {
                        $items[$item]['chalans']++;
                        $items[$item]['quantity'] += $quantity;
                    }
                }

                $totals[$item]['chalans'] += $items[$item]['chalans'];
                $totals[$item]['quantity'] += $items[$item]['quantity'];
            }

            $rows[] = ['school' => $school, ...$items];
        }

        $year = substr($month, 2, 2);
        $monthLabel = self::MONTH_NAMES[(int) $start->format('n')].'-'.self::bengaliDigits($year);

        return [
            'month' => $month,
            'month_label' => $monthLabel,
            'rows' => $rows,
            'totals' => $totals,
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
