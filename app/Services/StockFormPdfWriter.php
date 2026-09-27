<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class StockFormPdfWriter
{
    /** @param list<array{artwork: string, overlays: list<array<string, int|string>>}> $pages */
    public function render(array $pages): string
    {
        File::ensureDirectoryExists(storage_path('app/mpdf'));
        $fontDirs = (new ConfigVariables)->getDefaults()['fontDir'];
        $fontDirs[] = public_path('fonts');
        $fontData = (new FontVariables)->getDefaults()['fontdata'];
        $fontData['notobengali'] = ['R' => 'NotoSansBengali-Regular.ttf'];

        $pdf = new Mpdf([
            'mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'P',
            'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
            'margin_header' => 0, 'margin_footer' => 0,
            'fontDir' => $fontDirs, 'fontdata' => $fontData,
            'default_font' => 'notobengali', 'tempDir' => storage_path('app/mpdf'),
        ]);
        $pdf->SetAutoPageBreak(false);
        foreach ($pages as $index => $page) {
            if ($index > 0) {
                $pdf->AddPage();
            }
            $pdf->WriteHTML('<div style="font-size:1pt;line-height:1pt">&nbsp;</div>');
            $pdf->Image(public_path($page['artwork']), 0, 0, 210, 297, 'png', '', true, false);
            foreach ($page['overlays'] as $field) {
                $width = self::mm($field['width'], 993, 210);
                $height = self::mm($field['height'], 1404, 297);
                $size = $field['font_size'] * .6;
                $html = '<div style="box-sizing:border-box;width:'.$width.'mm;height:'.$height.'mm;overflow:hidden;background:#fff;color:#111;font-family:notobengali;font-size:'.$size.'pt;line-height:1.25;text-align:'.$field['align'].'">'.e($field['text']).'</div>';
                $pdf->WriteFixedPosHTML($html, self::mm($field['x'], 993, 210), self::mm($field['y'], 1404, 297), $width, $height, 'hidden');
            }
        }

        return $pdf->Output('', 'S');
    }

    private static function mm(int $value, int $reference, int $millimeters): float
    {
        return round($value * $millimeters / $reference, 4);
    }
}
