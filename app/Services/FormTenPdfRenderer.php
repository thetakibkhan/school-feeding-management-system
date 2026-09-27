<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class FormTenPdfRenderer
{
    public function __construct(private readonly FormTenTemplate $template) {}

    /** @param array<string, mixed> $form */
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
        $pdf->SetDocTemplate(base_path('resources/pdf/form-ten-template.pdf'));
        $pdf->WriteHTML('<div style="font-size:1pt;line-height:1pt">&nbsp;</div>');

        foreach ($this->template->overlays($form) as $field) {
            $x = $this->mm($field['x'], 993, 210);
            $y = $this->mm($field['y'], 1404, 297);
            $content = nl2br(e($field['text']));
            $font = $field['font_family'] === 'latin' ? 'dejavusans' : 'notobengali';
            $width = $this->mm($field['width'], 993, 210);
            $height = $this->mm($field['height'], 1404, 297);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Rect($x, $y, $width, $height, 'F');

            if ($field['text'] !== '') {
                $html = '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:'.$width.'mm;height:'.$height.'mm;margin:0;background:transparent;color:#111;font-family:'.$font.';font-size:'.$this->pointSize($field['font_size']).'pt;line-height:'.$field['line_height'].';font-weight:'.($field['bold'] ? 'bold' : 'normal').';"><tr><td style="padding:0;border:none;background:transparent;vertical-align:middle;text-align:'.$field['align'].';font-family:'.$font.';font-size:'.$this->pointSize($field['font_size']).'pt;line-height:'.$field['line_height'].';font-weight:'.($field['bold'] ? 'bold' : 'normal').';">'.$content.'</td></tr></table>';
                $pdf->WriteFixedPosHTML($html, $x, $y, $width, $height, 'hidden');
            }
        }

        return $pdf->Output('', 'S');
    }

    private function pointSize(int $pixelSize): float
    {
        return $pixelSize * 0.6;
    }

    private function mm(int $pixels, int $referencePixels, int $millimeters): float
    {
        return round($pixels * $millimeters / $referencePixels, 4);
    }
}
