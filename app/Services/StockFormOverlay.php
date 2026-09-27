<?php

namespace App\Services;

class StockFormOverlay
{
    /** @return array{x: int, y: int, width: int, height: int, text: string, font_size: int, align: string} */
    public static function field(int $x, int $y, int $width, int $height, string $text, int $fontSize = 12, string $align = 'center'): array
    {
        return compact('x', 'y', 'width', 'height', 'text', 'align') + ['font_size' => $fontSize];
    }

    public static function number(?int $value): string
    {
        return $value === null ? '' : FormSevenReportService::bengaliDigits((string) $value);
    }
}
