<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class FormSevenPdfRenderer
{
    private const PAGE_ROWS = [1 => 16, 2 => 26, 3 => 26, 4 => 26, 5 => 16];

    private const PAGE_OFFSETS = [1 => 0, 2 => 16, 3 => 42, 4 => 68, 5 => 94];

    private const FIRST_ROW_BOUNDS = [654, 703, 751, 801, 849, 899, 947, 996, 1045, 1094, 1142, 1191, 1240, 1289, 1337, 1387, 1436];

    private const OTHER_ROW_BOUNDS = [197, 246, 294, 343, 392, 441, 489, 539, 587, 637, 685, 734, 783, 832, 880, 930, 978, 1028, 1076, 1125, 1173, 1223, 1271, 1321, 1369, 1419, 1467];

    private const COLUMN_BOUNDS = [112, 164, 430, 526, 598, 680, 751, 826, 897, 966];

    /** @param array{month: string, month_label: string, rows: list<array<string, mixed>>, totals: array<string, array{chalans: int, quantity: int}>} $form */
    public function render(array $form): string
    {
        File::ensureDirectoryExists(storage_path('app/mpdf'));

        $fontConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $fontConfig['fontDir'];
        $fontDirs[] = public_path('fonts');

        $fontData = (new FontVariables)->getDefaults()['fontdata'];
        $fontData['notobengali'] = ['R' => 'NotoSansBengali-Regular.ttf'];

        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
            'fontDir' => $fontDirs,
            'fontdata' => $fontData,
            'default_font' => 'notobengali',
            'tempDir' => storage_path('app/mpdf'),
        ]);
        $pdf->SetAutoPageBreak(false);

        for ($page = 1; $page <= 6; $page++) {
            if ($page > 1) {
                $pdf->AddPage();
            }

            $pdf->Image(public_path('form-7/page-'.$page.'.png'), 0, 0, 210, 297, 'png', '', true, false);

            if ($page === 1) {
                $title = $form['month_label'].' মাসের বনরুটি (১২০ গ্রাম), সিদ্ধ ডিম (৬০ গ্রাম) ও কলা (১০০ গ্রাম) বিদ্যালয় পর্যায়ে সরবরাহের বিবরণী';
                $this->writeText($pdf, $title, 27.3, 63.7, 155.5, 6.3, '8.2pt', 'center', true);
                $this->writeText($pdf, (string) ($form['supplier_name'] ?? ''), $this->mm(291), $this->mm(448), $this->mm(129), $this->mm(30), '8pt', 'left');
            }

            if ($page > 5) {
                continue;
            }

            $rows = self::PAGE_ROWS[$page];
            $offset = self::PAGE_OFFSETS[$page];
            $bounds = $page === 1 ? self::FIRST_ROW_BOUNDS : self::OTHER_ROW_BOUNDS;
            $this->writeRows($pdf, $form, $rows, $offset, $bounds);

            if ($page === 5) {
                $this->writeTotals($pdf, $form);
                $this->writeNotes($pdf, $form);
            }
        }

        return $pdf->Output('', 'S');
    }

    /** @param array<string, mixed> $form
     * @param  list<int>  $bounds
     */
    private function writeRows(Mpdf $pdf, array $form, int $rowCount, int $offset, array $bounds): void
    {
        $x = $this->mm(112);
        $y = $this->mm($bounds[0]);
        $width = $this->mm(966 - 112);
        $height = $this->mm($bounds[$rowCount] - $bounds[0]);
        $html = '<table style="width:100%;border-collapse:collapse;table-layout:fixed;font-family:notobengali;font-size:7.6pt;line-height:1.25">';

        foreach (array_slice(self::COLUMN_BOUNDS, 0, -1) as $index => $left) {
            $html .= '<col style="width:'.$this->mm(self::COLUMN_BOUNDS[$index + 1] - $left).'mm">';
        }
        $html .= '<tbody>';

        for ($rowIndex = 0; $rowIndex < $rowCount; $rowIndex++) {
            $recordIndex = $offset + $rowIndex;
            $record = $form['rows'][$recordIndex] ?? null;
            $cells = $record === null ? array_fill(0, 9, '') : [
                $this->number($recordIndex + 1),
                (string) $record['school']->name,
                FormSevenReportService::bengaliDigits((string) $record['school']->emis_code),
                $this->number($record['bun']['chalans']),
                $this->number($record['bun']['quantity']),
                $this->number($record['egg']['chalans']),
                $this->number($record['egg']['quantity']),
                $this->number($record['banana']['chalans']),
                $this->number($record['banana']['quantity']),
            ];
            $rowHeight = $this->mm($bounds[$rowIndex + 1] - $bounds[$rowIndex]);
            $html .= '<tr>';

            foreach ($cells as $column => $cell) {
                $leftBorder = $column === 0 ? 'border-left:0.55pt solid #111;' : '';
                $topBorder = $rowIndex === 0 ? 'border-top:0.55pt solid #111;' : '';
                $alignment = $column === 1 ? 'text-align:left;padding-left:1mm;' : 'text-align:center;';
                $fontSize = $column === 1 ? '7.3pt' : ($column === 2 ? '6.6pt' : '7.6pt');
                $html .= '<td style="height:'.$rowHeight.'mm;overflow:hidden;white-space:nowrap;vertical-align:middle;'.$alignment.'font-size:'.$fontSize.';border-right:0.55pt solid #111;border-bottom:0.55pt solid #111;'.$leftBorder.$topBorder.'">'.e($cell).'</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $pdf->WriteFixedPosHTML($html, $x, $y, $width, $height, 'hidden');
    }

    /** @param array<string, mixed> $form */
    private function writeTotals(Mpdf $pdf, array $form): void
    {
        $x = $this->mm(112);
        $y = $this->mm(978);
        $html = '<table style="width:100%;height:100%;border-collapse:collapse;table-layout:fixed;font-family:notobengali;font-size:7.6pt;font-weight:bold;background:#f1f1f1"><tr>';
        $html .= '<td colspan="3" style="width:'.$this->mm(526 - 112).'mm;border:0.55pt solid #111;vertical-align:middle;text-align:left;padding-left:2mm">সর্বমোট</td>';
        foreach (['bun', 'egg', 'banana'] as $itemIndex => $item) {
            foreach (['chalans', 'quantity'] as $measureIndex => $measure) {
                $column = 3 + $itemIndex * 2 + $measureIndex;
                $width = $this->mm(self::COLUMN_BOUNDS[$column + 1] - self::COLUMN_BOUNDS[$column]);
                $value = $this->number($form['totals'][$item][$measure]);
                $html .= '<td style="width:'.$width.'mm;border:0.55pt solid #111;vertical-align:middle;text-align:center">'.e($value).'</td>';
            }
        }
        $html .= '</tr></table>';
        $pdf->WriteFixedPosHTML($html, $x, $y, $this->mm(966 - 112), $this->mm(1028 - 978), 'hidden');
    }

    /** @param array<string, mixed> $form */
    private function writeNotes(Mpdf $pdf, array $form): void
    {
        $schools = $this->number(count($form['rows']));
        $month = e($form['month_label']);
        $bun = $this->number($form['totals']['bun']['quantity']);
        $egg = $this->number($form['totals']['egg']['quantity']);
        $banana = $this->number($form['totals']['banana']['quantity']);
        if ($form['fixture_school_count'] > 0) {
            $notes = [
                'এই ফরমে প্রদর্শিত সেপ্টেম্বরের assessment/demo fixture পরিমাণসমূহ সরবরাহকৃত চাহিদার পূর্ণ সরবরাহ ধরে (delivered = demand) দেখানো হয়েছে। এগুলো মাঠকর্মীর প্রকৃত এন্ট্রি নয় এবং কোনো চালান তৈরি করে না।',
                'প্রকৃত সরবরাহ ও চালান যাচাইয়ের আগে এই ফিক্সচার পরিমাণকে প্রকৃত সরবরাহের প্রমাণ হিসেবে ব্যবহার করা যাবে না।',
            ];
        } else {
            $notes = [
                'উপযুক্ত বিবরণ অনুযায়ী অত্র উপজেলার '.$schools.' টি সরকারি প্রাথমিক বিদ্যালয়ে '.$month.' মাসের স্পেসিফিকেশন অনুযায়ী সরবরাহকৃত '.$bun.' প্যাকেট বনরুটি, '.$egg.' পিস সিদ্ধ ডিম ও '.$banana.' পিস কলা সরবরাহের চালানের মূল কপি অত্র কার্যালয়ে সংরক্ষিত আছে।',
                'এমতাবস্থায়, উক্ত সরবরাহকারী ঠিকাদারকে '.$month.' মাসের '.$bun.' প্যাকেট বনরুটি, '.$egg.' পিস সিদ্ধ ডিম ও '.$banana.' পিস কলা সরবরাহের বিল পরিশোধ করার সুপারিশ করা হলো।',
            ];
        }
        $html = '<div style="width:100%;height:100%;background:#fff;font-family:notobengali;font-size:8.1pt;line-height:1.55">'
            .'<p style="margin:0 0 5mm">'.e($notes[0]).'</p>'
            .'<p style="margin:0">'.e($notes[1]).'</p></div>';
        $pdf->WriteFixedPosHTML($html, $this->mm(112), $this->mm(1069), $this->mm(854), $this->mm(179), 'hidden');
    }

    private function writeText(Mpdf $pdf, string $text, float $x, float $y, float $width, float $height, string $fontSize, string $align, bool $bold = false): void
    {
        $html = '<div style="width:100%;height:100%;background:#fff;font-family:notobengali;font-size:'.$fontSize.';font-weight:'.($bold ? 'bold' : 'normal').';text-align:'.$align.';line-height:1.2">'.e($text).'</div>';
        $pdf->WriteFixedPosHTML($html, $x, $y, $width, $height, 'hidden');
    }

    private function number(int|string $value): string
    {
        return FormSevenReportService::bengaliDigits(number_format((int) $value));
    }

    private function mm(int|float $pixels): float
    {
        return round($pixels * 210 / 1075, 4);
    }
}
