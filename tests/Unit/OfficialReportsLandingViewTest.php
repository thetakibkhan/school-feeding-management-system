<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OfficialReportsLandingViewTest extends TestCase
{
    public function test_report_period_fields_are_not_shown_on_the_reports_landing_page(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/reports/official/index.blade.php');

        $this->assertIsString($view);
        $this->assertStringNotContainsString('Supplier/contractor for', $view);
        $this->assertStringNotContainsString('id="invoice-number"', $view);
        $this->assertStringNotContainsString('id="invoice-date"', $view);
        $this->assertStringNotContainsString('id="contract-number"', $view);
        $this->assertStringNotContainsString('id="bank-account-name"', $view);
        $this->assertStringNotContainsString('id="bank-account-number"', $view);
        $this->assertStringNotContainsString('id="bank-name"', $view);
        $this->assertStringNotContainsString('id="bank-branch"', $view);
        $this->assertStringNotContainsString('id="bank-routing-number"', $view);
        $this->assertStringNotContainsString('Save period details', $view);
    }

    public function test_form_ten_uses_generated_invoice_numbers_and_keeps_optional_metadata_and_printing(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/reports/official/form-ten.blade.php');

        $this->assertIsString($view);
        $this->assertStringContainsString('id="supplier-name"', $view);
        $this->assertStringNotContainsString('id="invoice-number"', $view);
        $this->assertStringNotContainsString('name="invoice_number"', $view);
        $this->assertStringContainsString('id="invoice-date"', $view);
        $this->assertStringContainsString('id="contract-number"', $view);
        $this->assertStringContainsString('id="bank-account-name"', $view);
        $this->assertStringContainsString('id="bank-account-number"', $view);
        $this->assertStringContainsString('id="bank-name"', $view);
        $this->assertStringContainsString('id="bank-branch"', $view);
        $this->assertStringContainsString('id="bank-routing-number"', $view);
        $this->assertStringContainsString('Download PDF', $view);
        $this->assertStringContainsString('window.print()', $view);
    }
}
