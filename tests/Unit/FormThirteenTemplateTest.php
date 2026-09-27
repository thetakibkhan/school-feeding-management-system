<?php

namespace Tests\Unit;

use App\Models\StockReportPeriod;
use App\Services\FormThirteenTemplate;
use App\Services\StockFormPdfWriter;
use Tests\TestCase;

class FormThirteenTemplateTest extends TestCase
{
    public function test_totals_sum_known_school_values_and_leave_unknown_rows_blank(): void
    {
        $known = [
            'school' => null,
            'items' => [
                'bun' => ['opening' => 3, 'recorded_received' => 10, 'distributed' => 8],
                'egg' => ['opening' => 0, 'recorded_received' => 4, 'distributed' => 4],
                'banana' => ['opening' => 2, 'recorded_received' => 2, 'distributed' => 3],
            ],
        ];
        $unknown = [
            'school' => null,
            'items' => [
                'bun' => ['opening' => null, 'recorded_received' => null, 'received' => 0, 'available' => 0, 'distributed' => null, 'closing' => 0],
                'egg' => ['opening' => null, 'recorded_received' => null, 'received' => 0, 'available' => 0, 'distributed' => null, 'closing' => 0],
                'banana' => ['opening' => null, 'recorded_received' => null, 'received' => 0, 'available' => 0, 'distributed' => null, 'closing' => 0],
            ],
        ];
        $report = [
            'month' => '2026-09',
            'month_label' => 'সেপ্টেম্বর',
            'school_count' => 2,
            'schools' => [$known, $unknown],
            'totals' => [],
        ];

        $pages = (new FormThirteenTemplate)->pages(
            $report,
            new StockReportPeriod(['district_name' => null, 'upazila_name' => null, 'supplier_name' => null]),
        );

        $this->assertSame(['১৩', '৮', '৫', '৪', '৪', '০', '৪', '৩', '১'], array_column($pages[4]['overlays'], 'text'));
        $unknownRow = array_slice($pages[0]['overlays'], 15, 12);
        $this->assertSame(['২', '', '', '', '', '', '', '', '', '', '', ''], array_column($unknownRow, 'text'));

        $pdf = app(StockFormPdfWriter::class)->render($pages);
        $this->assertStringStartsWith('%PDF', $pdf);
    }
}
