<?php

namespace App\Services;

use App\Models\StockReportPeriod;

class FormTwelveTemplate
{
    /** @param array<string, mixed> $report
     * @param  array<string, mixed>  $page
     * @return list<array<string, int|string>>
     */
    public function overlays(array $report, StockReportPeriod $period, array $page): array
    {
        $field = StockFormOverlay::field(...);
        $school = $page['school'];
        $input = $page['input'];
        $month = 'মাস: '.$report['month_label'].'    সাল: '.FormSevenReportService::bengaliDigits(substr($report['month'], 0, 4));
        $fields = [
            $field(418, 155, 180, 26, $month, 15),
            $field(170, 198, 326, 32, $school->name, 15),
            $field(607, 198, 313, 32, FormSevenReportService::bengaliDigits($school->emis_code), 14),
            $field(112, 234, 383, 31, $period->district_name, 14),
            $field(500, 234, 420, 31, 'উপজেলা: '.$period->upazila_name, 14, 'left'),
            $field(170, 271, 325, 30, (string) ($input?->union_name ?? ''), 14),
            $field(631, 271, 289, 30, (string) ($input?->cluster_name ?? ''), 14),
            $field(170, 307, 153, 31, StockFormOverlay::number($input?->boy_count), 14),
            $field(462, 307, 145, 31, StockFormOverlay::number($input?->girl_count), 14),
            $field(765, 307, 153, 31, StockFormOverlay::number($page['student_count']), 14),
        ];

        $columns = [
            'milk' => [127, 180, 234, 287],
            'biscuit' => [288, 341, 395, 448],
            'bun' => [448, 501, 554, 608],
            'egg' => [608, 661, 715, 768],
            'banana' => [768, 822, 876, 929],
        ];
        foreach ($columns as $item => $edges) {
            foreach (['available', 'distributed', 'closing'] as $index => $quantity) {
                $fields[] = $field($edges[$index] + 2, 517, $edges[$index + 1] - $edges[$index] - 4, 35, StockFormOverlay::number($page['items'][$item][$quantity]), 12);
            }
        }

        return $fields;
    }
}
