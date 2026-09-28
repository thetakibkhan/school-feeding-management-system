<?php

namespace App\Services;

class FormTenTemplate
{
    /** @param array<string, mixed> $form
     * @return list<array{x: int, y: int, width: int, height: int, text: string, font_size: int, bold: bool, align: string, line_height: float, font_family: string}>
     */
    public function overlays(array $form): array
    {
        $items = $form['items'];
        $period = $form['period'];
        $serviceRate = $form['related_service_unit_price'];
        $serviceRateText = $serviceRate === null ? '' : FormSevenReportService::bengaliDigits(number_format((float) $serviceRate, 3, '.', ''));
        $quantity = static fn (string $key): string => FormSevenReportService::bengaliDigits((string) $items[$key]['delivered_quantity']);
        $price = static fn (string $key): string => $items[$key]['unit_price'] === null
            ? ''
            : FormSevenReportService::bengaliDigits($items[$key]['unit_price']);
        $lineTotal = static fn (string $key): string => $items[$key]['line_total'] === null
            ? ''
            : FormSevenReportService::bengaliDigits($items[$key]['line_total']);
        $month = $form['month_label'];
        $chalanCount = FormSevenReportService::bengaliDigits((string) $form['chalan_count']);
        $schoolCount = FormSevenReportService::bengaliDigits((string) $form['school_count']);
        $grandTotal = $form['grand_total_formatted'];
        $grandTotalWords = (string) $form['grand_total_words'];
        $isAssessmentFixture = $form['fixture_school_count'] > 0;
        $quantitySourceNote = $isAssessmentFixture
            ? 'সেপ্টেম্বরের assessment/demo fixture পরিমাণসমূহ সরবরাহকৃত চাহিদার পূর্ণ সরবরাহ ধরে (delivered = demand) দেখানো হয়েছে; এগুলো মাঠকর্মীর প্রকৃত এন্ট্রি নয়।'
            : 'উপর্যুক্ত বিবরণ অনুযায়ী অত্র উপজেলার '.$schoolCount.' টি সরকারি প্রাথমিক বিদ্যালয়ে '.$month.' মাসের স্পেসিফিকেশন অনুযায়ী বনরুটি, সিদ্ধ ডিম ও কলা সরবরাহের '.$chalanCount.' টি চালানের কপি অত্র কার্যালয়ে সংরক্ষিত আছে। নিম্ন স্বাক্ষরকারী কর্তৃক স্বাক্ষরিত ফরম নম্বর ৭ ও ফরম নম্বর ১৩ এতদসঙ্গে প্রেরণ করা হলো।';
        $paymentNote = $isAssessmentFixture
            ? 'assessment/demo fixture পরিমাণের ভিত্তিতে হিসাব করা '.$grandTotal.'/- টাকা; প্রকৃত সরবরাহ যাচাই না হওয়া পর্যন্ত এটি প্রকৃত পরিশোধযোগ্য বিল নয়।'
            : 'এমতাবস্থায়, উক্ত ঠিকাদারকে '.$month.' মাসের '.$quantity('bun').' প্যাকেট বনরুটি, '.$quantity('boiled_egg').' পিস সিদ্ধ ডিম ও '.$quantity('banana').' পিস কলা সরবরাহের '.$grandTotal.'/- টাকার বিল পরিশোধ করার সুপারিশ করা হলো।';
        $billRequest = 'বর্ণিত '.$grandTotal.'/- টাকার বিল প্রদানের জন্য অনুরোধ করা হলো';
        $attachmentNote = 'সংযুক্তি: এই বিল সম্পর্কিত '.$chalanCount.' টি চালানের মূল কপি।';

        $fields = [
            $this->field(208, 240, 180, 20, (string) ($period->invoice_number ?? ''), 18, false, 'left'),
            $this->field(825, 240, 160, 20, $this->date($period->invoice_date), 18, false, 'left'),
            $this->field(183, 398, 220, 20, (string) ($period->contract_number ?? ''), 18, false, 'left'),
            $this->field(120, 424, 820, 28, 'বিষয়: আনোয়ারা উপজেলার '.$month.' মাসের বনরুটি, সিদ্ধ ডিম ও কলা সরবরাহের বিক্রয় ইনভয়েস।', 17, false, 'left'),
            $this->field(120, 895, 794, 65, $quantitySourceNote, 17, false, 'left', 1.26),
            $this->field(120, 963, 801, 44, $paymentNote, 17, false, 'left', 1.26),
            $this->field(120, 678, 794, 20, '', 17, false, 'left'),
            $this->field(120, 703, 794, 20, $billRequest, 17, false, 'left'),
            $this->field(120, 729, 794, 20, $attachmentNote, 17, false, 'left'),
            $this->field(216, 779, 314, 20, (string) ($period->bank_account_name ?? ''), 18, false, 'left'),
            $this->field(206, 800, 324, 20, (string) ($period->bank_account_number ?? ''), 18, false, 'left'),
            $this->field(213, 822, 317, 20, (string) ($period->bank_name ?? ''), 18, false, 'left'),
            $this->field(199, 843, 331, 20, (string) ($period->bank_branch ?? ''), 18, false, 'left'),
            $this->field(207, 864, 323, 20, (string) ($period->bank_routing_number ?? ''), 18, false, 'left'),
            $this->field(694, 757, 214, 20, (string) ($form['supplier_name'] ?? ''), 17, false, 'left'),
        ];

        foreach (['bun' => 555, 'boiled_egg' => 588, 'banana' => 621] as $key => $top) {
            $fields[] = $this->field(268, $top + 3, 101, 24, $quantity($key), 18);
            $fields[] = $this->field(379, $top + 3, 95, 24, $price($key), 18);
            $fields[] = $this->field(484, $top + 3, 95, 24, $lineTotal($key), 18, false, 'right');
            $fields[] = $this->field(590, $top + 3, 97, 24, $serviceRateText, 18);
            $fields[] = $this->field(698, $top + 3, 99, 24, '০', 18);
            $fields[] = $this->field(808, $top + 3, 95, 24, $lineTotal($key), 18, false, 'right');
        }

        $fields[] = $this->field(484, 657, 95, 18, $grandTotal, 16, true, 'right');
        $fields[] = $this->field(808, 657, 95, 18, $grandTotal, 16, true, 'right');
        $fields[] = $this->field(120, 682, 794, 21, 'কথায়: '.$grandTotalWords, 17, true, 'left');

        return $fields;
    }

    /** @return array{x: int, y: int, width: int, height: int, text: string, font_size: int, bold: bool, align: string, line_height: float, font_family: string} */
    private function field(int $x, int $y, int $width, int $height, string $text, int $fontSize, bool $bold = false, string $align = 'center', float $lineHeight = 1.1): array
    {
        return compact('x', 'y', 'width', 'height') + [
            'text' => $text,
            'font_size' => $fontSize,
            'bold' => $bold,
            'align' => $align,
            'line_height' => $lineHeight,
            'font_family' => preg_match('/\AAN-\d{5,}\z/', $text) === 1 ? 'latin' : 'bangla',
        ];
    }

    private function date(mixed $date): string
    {
        if ($date === null) {
            return '';
        }

        $monthNames = [
            1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
            5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
            9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
        ];

        return FormSevenReportService::bengaliDigits($date->format('j')).' '.$monthNames[(int) $date->format('n')].' '.FormSevenReportService::bengaliDigits($date->format('Y'));
    }
}
