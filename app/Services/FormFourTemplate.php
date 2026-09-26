<?php

namespace App\Services;

use App\Models\School;

class FormFourTemplate
{
    /** @param array<string, mixed> $schoolPage
     * @return list<array{x: float, y: float, width: float, height: float, text: string, font_size: int, bold: bool, align: string, border: bool}>
     */
    public function overlays(array $schoolPage, string $monthLabel, string $year): array
    {
        /** @var School $school */
        $school = $schoolPage['school'];
        $fields = [
            $this->field(438, 124, 160, 26, 'মাস: '.$monthLabel.'   সাল: '.FormSevenReportService::bengaliDigits($year), 11),
            $this->field(141, 169, 352, 24, $school->name, 11, false, 'left'),
            $this->field(545, 169, 375, 24, FormSevenReportService::bengaliDigits($school->emis_code), 11, false, 'left'),
        ];

        $rows = collect($schoolPage['daily_rows'])->keyBy('day');
        $columns = [
            'date' => [107, 79],
            'chalan_number' => [186, 94],
            'chalan_date' => [280, 78],
            'bun' => [358, 78],
            'boiled_egg' => [436, 69],
            'banana' => [505, 70],
            'untracked_food_one' => [575, 69],
            'untracked_food_two' => [644, 70],
        ];

        $hasThirtyOneDays = $rows->count() === 31;
        $dailyColumns = $columns;
        if ($hasThirtyOneDays) {
            // Fit the extra day inside the same table body; keep one A4 page per school.
            $fields[] = $this->field(64, 349.5, 866, 675.5, '', 10);
            $dailyColumns = ['day' => [64, 43]] + $columns + [
                'recipient_signature' => [714, 138],
                'remarks' => [852, 78],
            ];
        }

        for ($day = 1; $day <= ($hasThirtyOneDays ? 31 : 30); $day++) {
            $row = $rows->get($day);
            // The source template has a numbered column-key row above day 1.
            // Daily records begin at y=350, not at the key row (y=328).
            $rowHeight = $hasThirtyOneDays ? 675.5 / 31 : 22.53;
            $top = 349.5 + (($day - 1) * $rowHeight);
            $dateText = $row === null
                ? ''
                : FormSevenReportService::bengaliDigits(date('d/m/Y', strtotime($row['date'])));
            $chalanNumber = $row === null ? '' : ($row['chalan_number'] ?: '-');
            $chalanDate = $row === null || $row['chalan_date'] === null
                ? ($row === null ? '' : '-')
                : FormSevenReportService::bengaliDigits(date('d/m/Y', strtotime($row['chalan_date'])));
            $quantities = $row['quantities'] ?? [];
            $texts = [
                'day' => FormSevenReportService::bengaliDigits((string) $day),
                'date' => $dateText,
                'chalan_number' => $chalanNumber,
                'chalan_date' => $chalanDate,
                'bun' => isset($quantities['bun']) ? FormSevenReportService::bengaliDigits((string) $quantities['bun']) : '',
                'boiled_egg' => isset($quantities['boiled_egg']) ? FormSevenReportService::bengaliDigits((string) $quantities['boiled_egg']) : '',
                'banana' => isset($quantities['banana']) ? FormSevenReportService::bengaliDigits((string) $quantities['banana']) : '',
                'untracked_food_one' => '',
                'untracked_food_two' => '',
                'recipient_signature' => '',
                'remarks' => '',
            ];

            foreach ($dailyColumns as $key => [$x, $width]) {
                $inset = $hasThirtyOneDays ? 0 : 1.5;
                $fields[] = $this->field($x + $inset, $top + $inset, $width - 2 * $inset, $rowHeight - 2 * $inset, $texts[$key], 10, false, 'center', $hasThirtyOneDays);
            }
        }

        foreach (array_slice($columns, 3, null, true) as $key => [$x, $width]) {
            $total = $schoolPage['totals'][$key] ?? null;
            $text = $total === null ? '' : FormSevenReportService::bengaliDigits((string) $total);
            $fields[] = $this->field($x + 1.5, 1027, $width - 3, 19, $text, 10, true);
        }

        return $fields;
    }

    /** @return array{x: float, y: float, width: float, height: float, text: string, font_size: int, bold: bool, align: string, border: bool} */
    private function field(float $x, float $y, float $width, float $height, string $text, int $fontSize, bool $bold = false, string $align = 'center', bool $border = false): array
    {
        return compact('x', 'y', 'width', 'height', 'text', 'bold', 'align', 'border') + [
            'font_size' => $fontSize,
        ];
    }
}
