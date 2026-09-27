<?php

namespace App\Services;

use App\Models\StockReportPeriod;

class FormThirteenTemplate
{
    private const FIRST_PAGE_LINES = [596, 633, 671, 709, 747, 784, 823, 860, 898, 936, 974, 1011, 1050, 1087, 1125, 1163, 1201, 1238, 1276, 1314, 1352];

    private const LATER_PAGE_LINES = [192, 229, 268, 305, 343, 381, 419, 456, 495, 532, 570, 607, 646, 683, 721, 759, 797, 834, 873, 910, 949, 986, 1024, 1061, 1100, 1137, 1175, 1213, 1251, 1288, 1327];

    private const DATA_EDGES = [106, 147, 356, 431, 499, 555, 593, 657, 709, 748, 806, 850, 889];

    /** @param array<string, mixed> $report
     * @return list<array{artwork: string, overlays: list<array<string, int|string>>}>
     */
    public function pages(array $report, StockReportPeriod $period): array
    {
        $schools = $report['schools'];
        $pages = [];
        for ($pageNumber = 1; $pageNumber <= 5; $pageNumber++) {
            $fields = [];
            if ($pageNumber === 1) {
                $fields[] = StockFormOverlay::field(416, 239, 169, 22, 'মাস: '.$report['month_label'].'    সাল: '.FormSevenReportService::bengaliDigits(substr($report['month'], 0, 4)), 14);
                $fields[] = StockFormOverlay::field(375, 293, 259, 23, 'জেলা: '.$period->district_name.'    উপজেলা: '.$period->upazila_name, 13);
                $fields[] = StockFormOverlay::field(297, 395, 591, 25, (string) $period->supplier_name, 14, 'left');
            }
            if ($pageNumber < 5) {
                $lines = $pageNumber === 1 ? self::FIRST_PAGE_LINES : self::LATER_PAGE_LINES;
                $offset = $pageNumber === 1 ? 0 : 20 + ($pageNumber - 2) * 30;
                foreach (array_slice($schools, $offset, count($lines) - 1) as $index => $school) {
                    $fields = array_merge($fields, $this->row($school, $offset + $index + 1, $lines[$index], $lines[$index + 1]));
                }
                // Erase all reference-month sample values even when the current school list is shorter.
                for ($index = count(array_slice($schools, $offset, count($lines) - 1)); $index < count($lines) - 1; $index++) {
                    $fields = array_merge($fields, $this->row(null, null, $lines[$index], $lines[$index + 1]));
                }
            } else {
                $fields = array_merge($fields, $this->row(['school' => null, 'items' => $report['totals']], null, 192, 229, true));
            }
            $pages[] = ['artwork' => 'form-13/page-'.$pageNumber.'.png', 'overlays' => $fields];
        }

        return $pages;
    }

    /** @param array<string, mixed>|null $entry
     * @return list<array<string, int|string>>
     */
    private function row(?array $entry, ?int $serial, int $top, int $bottom, bool $totals = false): array
    {
        $edges = self::DATA_EDGES;
        $school = $entry['school'] ?? null;
        $values = [
            $totals ? 'সর্বমোট' : StockFormOverlay::number($serial),
            $totals ? '' : (string) ($school?->name ?? ''),
            $totals ? '' : FormSevenReportService::bengaliDigits((string) ($school?->emis_code ?? '')),
        ];
        foreach (['bun', 'egg', 'banana'] as $item) {
            foreach (['available', 'distributed', 'closing'] as $quantity) {
                $values[] = StockFormOverlay::number($entry['items'][$item][$quantity] ?? null);
            }
        }
        $fields = [];
        foreach ($values as $index => $value) {
            // Keep the reference's printed grand-total label on the last page.
            if ($totals && $index < 3) {
                continue;
            }
            $fontSize = $index === 2 ? 9 : ($index === 1 ? 10 : 11);
            $fields[] = StockFormOverlay::field($edges[$index] + 2, $top + 2, $edges[$index + 1] - $edges[$index] - 4, $bottom - $top - 4, $value, $fontSize);
        }

        return $fields;
    }
}
