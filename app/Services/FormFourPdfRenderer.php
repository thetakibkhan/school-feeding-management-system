<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class FormFourPdfRenderer
{
    public function __construct(private readonly FormFourTemplate $template) {}

    /** @param array<string, mixed> $report */
    public function render(array $report): string
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

        foreach ($report['schools'] as $index => $schoolPage) {
            if ($index > 0) {
                $pdf->AddPage();
            }

            if ($index === 0) {
                $pdf->WriteHTML('<div style="font-size:1pt;line-height:1pt">&nbsp;</div>');
            }
            $pdf->Image(public_path('form-4/page-1.png'), 0, 0, 210, 297, 'png', '', true, false);

            foreach ($this->template->overlays($schoolPage, $report['month_label'], substr($report['month'], 0, 4)) as $field) {
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetDrawColor(0, 0, 0);
                $pdf->SetLineWidth(0.1);
                $pdf->Rect(
                    $this->mm($field['x'], 993, 210),
                    $this->mm($field['y'], 1404, 297),
                    $this->mm($field['width'], 993, 210),
                    $this->mm($field['height'], 1404, 297),
                    $field['border'] ? 'DF' : 'F',
                );
                if ($field['text'] === '') {
                    continue;
                }
                $content = nl2br(e($field['text']));
                $padding = max(0, ($field['height'] - $field['font_size'] * 1.4) / 2);
                $html = '<div style="color:#111;font-family:notobengali;font-size:'.$this->pointSize($field['font_size']).'pt;line-height:1.4;font-weight:'.($field['bold'] ? 'bold' : 'normal').';text-align:'.$field['align'].'">'.$content.'</div>';
                $pdf->WriteFixedPosHTML(
                    $html,
                    $this->mm($field['x'], 993, 210),
                    $this->mm($field['y'] + $padding, 1404, 297),
                    $this->mm($field['width'], 993, 210),
                    $this->mm($field['height'] - $padding, 1404, 297),
                    'hidden',
                );
            }
        }

        return $pdf->Output('', 'S');
    }

    private function pointSize(int $pixelSize): float
    {
        return $pixelSize * 0.6;
    }

    private function mm(float $pixels, int $referencePixels, int $millimeters): float
    {
        return round($pixels * $millimeters / $referencePixels, 4);
    }
}
