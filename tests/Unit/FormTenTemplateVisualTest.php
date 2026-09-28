<?php

namespace Tests\Unit;

use App\Services\FormTenTemplate;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class FormTenTemplateVisualTest extends TestCase
{
    public function test_dynamic_text_uses_form_ten_reference_positions_and_does_not_repeat_sample_values(): void
    {
        $form = [
            'month' => '2026-09',
            'month_label' => 'সেপ্টেম্বর-২০২৬',
            'period' => (object) [
                'invoice_number' => 'AN-00001',
                'invoice_date' => Carbon::parse('2026-09-30'),
                'contract_number' => null,
                'bank_account_name' => null,
                'bank_account_number' => null,
                'bank_name' => null,
                'bank_branch' => null,
                'bank_routing_number' => null,
            ],
            'supplier_name' => 'স্বদেশ পল্লী লিমিটেড',
            'items' => [
                'bun' => ['delivered_quantity' => 10, 'unit_price' => '22.883', 'line_total' => '২২৮.৮'],
                'boiled_egg' => ['delivered_quantity' => 10, 'unit_price' => '13.543', 'line_total' => '১৩৫.৪'],
                'banana' => ['delivered_quantity' => 10, 'unit_price' => '9.807', 'line_total' => '৯৮.১'],
            ],
            'related_service_unit_price' => '0.000',
            'school_count' => 110,
            'fixture_school_count' => 0,
            'grand_total_formatted' => '৪৬২',
            'grand_total_words' => 'চার শত বাষট্টি টাকা মাত্র।',
            'chalan_count' => 3,
        ];

        $overlays = (new FormTenTemplate)->overlays($form);
        $invoiceMask = $this->fieldAt($overlays, 208, 240);
        $this->assertSame(180, $invoiceMask['width']);
        $this->assertSame(18, $invoiceMask['font_size']);
        $this->assertSame('latin', $invoiceMask['font_family']);
        $this->assertSame('AN-00001', $invoiceMask['text']);
        $invoiceDate = $this->fieldAt($overlays, 825, 240);
        $this->assertSame('৩০ সেপ্টেম্বর ২০২৬', $invoiceDate['text']);
        $this->assertSame(160, $invoiceDate['width']);

        $subject = $this->fieldAt($overlays, 120, 424);
        $this->assertSame(17, $subject['font_size']);
        $this->assertStringContainsString('সেপ্টেম্বর-২০২৬', $subject['text']);

        $bunTotal = $this->fieldAt($overlays, 484, 558);
        $this->assertSame('right', $bunTotal['align']);
        $this->assertSame('২২৮.৮', $bunTotal['text']);

        $supplier = $this->fieldAt($overlays, 694, 757);
        $this->assertSame('স্বদেশ পল্লী লিমিটেড', $supplier['text']);

        $amountMask = $this->fieldAt($overlays, 120, 678);
        $this->assertSame('', $amountMask['text']);
        $amountWords = $this->fieldAt($overlays, 120, 682);
        $this->assertSame('কথায়: চার শত বাষট্টি টাকা মাত্র।', $amountWords['text']);
        $printedTotal = $this->fieldAt($overlays, 120, 703);
        $this->assertSame('বর্ণিত ৪৬২/- টাকার বিল প্রদানের জন্য অনুরোধ করা হলো', $printedTotal['text']);
        $this->assertSame('left', $printedTotal['align']);
        $chalanAttachment = $this->fieldAt($overlays, 120, 729);
        $this->assertSame('left', $chalanAttachment['align']);
        $this->assertSame('সংযুক্তি: এই বিল সম্পর্কিত ৩ টি চালানের মূল কপি।', $chalanAttachment['text']);

        $overlayText = implode(' ', array_column($overlays, 'text'));
        $this->assertStringNotContainsString('AN-00004', $overlayText);
        $this->assertStringNotContainsString('১,১৯,৫৫,৭২০', $overlayText);
        $this->assertStringNotContainsString('৩৭৩৮', $overlayText);
        $this->assertStringNotContainsString('৩৩৬৩৪৭', $overlayText);
        $this->assertStringContainsString('উপর্যুক্ত বিবরণ', $overlayText);

        $fixtureOverlays = (new FormTenTemplate)->overlays([
            ...$form,
            'fixture_school_count' => 1,
        ]);
        $fixtureNote = $this->fieldAt($fixtureOverlays, 120, 895);
        $this->assertStringContainsString('assessment/demo fixture', $fixtureNote['text']);
        $this->assertStringContainsString('delivered = demand', $fixtureNote['text']);
        $this->assertStringContainsString('মাঠকর্মীর প্রকৃত এন্ট্রি নয়', $fixtureNote['text']);
        $fixtureAttachment = $this->fieldAt($fixtureOverlays, 120, 729);
        $this->assertStringContainsString('কোনো কাল্পনিক চালান', $fixtureAttachment['text']);
    }

    /** @param list<array{x: int, y: int, width: int, height: int, text: string, font_size: int, bold: bool, align: string, line_height: float, font_family: string}> $overlays
     * @return array{x: int, y: int, width: int, height: int, text: string, font_size: int, bold: bool, align: string, line_height: float, font_family: string}
     */
    private function fieldAt(array $overlays, int $x, int $y): array
    {
        foreach ($overlays as $overlay) {
            if ($overlay['x'] === $x && $overlay['y'] === $y) {
                return $overlay;
            }
        }

        $this->fail("No Form 10 overlay exists at {$x},{$y}.");
    }
}
