<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\FoodSchedule;
use App\Models\NonWorkingDate;
use App\Models\OfficialReportPeriod;
use App\Models\School;
use App\Models\SchoolMonthlyPlanningQuantity;
use App\Models\SchoolStudentCount;
use App\Models\User;
use App\Services\FormTenInvoiceNumberGenerator;
use App\Services\FormTenReportService;
use Database\Seeders\AnwaraSchoolSeeder;
use Database\Seeders\DemandSetupSeeder;
use Database\Seeders\OfficialReportPeriodSeeder;
use Database\Seeders\SeptemberSchoolPlanningQuantitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormTenReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_september_delivery_quantities_override_assessment_fixture_quantities(): void
    {
        $this->seed([DemandSetupSeeder::class, OfficialReportPeriodSeeder::class]);
        $firstSchool = $this->school('AN-001', '11111111111', 100);
        $secondSchool = $this->school('AN-002', '22222222222', 200);
        foreach ([$firstSchool, $secondSchool] as $school) {
            SchoolMonthlyPlanningQuantity::query()->create([
                'school_id' => $school->id,
                'month' => '2026-09',
                'bun_quantity' => 1000,
                'egg_quantity' => 1000,
                'banana_quantity' => 1000,
                'source_document' => 'GPSFP_School List_Anwara Upazila.pdf',
            ]);
        }
        $staff = User::factory()->create(['role' => UserRole::FieldStaff]);
        $this->delivery($firstSchool, $staff, '2026-09-02', 100, 10, 0);
        $this->delivery($secondSchool, $staff, '2026-09-02', 50, 10, 0);

        $form = app(FormTenReportService::class)->forMonth('2026-09');

        $this->assertSame('স্বদেশ পল্লী লিমিটেড', $form['supplier_name']);
        $this->assertSame(40, $form['missing_delivery_count']);
        $this->assertSame([
            'daily_demand' => 4320,
            'distribution_days' => 16,
            'delivered_quantity' => 150,
            'unit_price' => '22.883',
            'line_total' => '৩৪৩২.৫',
        ], $form['items']['bun']);
        $this->assertSame([
            'daily_demand' => 3240,
            'distribution_days' => 12,
            'delivered_quantity' => 20,
            'unit_price' => '13.543',
            'line_total' => '২৭০.৯',
        ], $form['items']['boiled_egg']);
        $this->assertSame(3703, $form['grand_total']);
        $this->assertSame(2, $form['chalan_count']);
        $this->assertSame(0, $form['fixture_school_count']);
        $this->assertSame('actual_deliveries', $form['quantity_source']);
        $this->assertDatabaseCount('deliveries', 2);
        $this->assertSame(21, FoodSchedule::query()->count());
        $this->assertSame(0, NonWorkingDate::query()->count());
    }

    public function test_september_fixture_quantities_are_assumed_delivered_without_fabricating_chalans(): void
    {
        $this->seed([DemandSetupSeeder::class, OfficialReportPeriodSeeder::class]);
        $school = $this->school('AN-001', '11111111111', 100);
        SchoolMonthlyPlanningQuantity::query()->create([
            'school_id' => $school->id,
            'month' => '2026-09',
            'bun_quantity' => 160,
            'egg_quantity' => 120,
            'banana_quantity' => 50,
            'source_document' => 'GPSFP_School List_Anwara Upazila.pdf',
        ]);

        $form = app(FormTenReportService::class)->forMonth('2026-09');

        $this->assertSame(1, $form['fixture_school_count']);
        $this->assertSame('assessment_fixture', $form['quantity_source']);
        $this->assertSame(160, $form['items']['bun']['delivered_quantity']);
        $this->assertSame(120, $form['items']['boiled_egg']['delivered_quantity']);
        $this->assertSame(50, $form['items']['banana']['delivered_quantity']);
        $this->assertSame(0, $form['chalan_count']);
        $this->assertStringContainsString('supplied September demand quantities are shown', implode(' ', $form['warnings']));
        $this->assertDatabaseCount('deliveries', 0);
        $this->assertNull($form['period']->invoice_date);
        $this->assertNull($form['period']->contract_number);
        $this->assertNull($form['period']->bank_account_number);

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get(route('admin.reports.form-ten', ['month' => '2026-09']))
            ->assertOk()
            ->assertSee('Some report values need attention', false)
            ->assertSee('the supplied September demand quantities are shown', false)
            ->assertDontSee('assessment/demo', false);
    }

    public function test_seeded_september_assessment_fixtures_populate_form_ten_from_source_totals(): void
    {
        $this->seed([
            AnwaraSchoolSeeder::class,
            DemandSetupSeeder::class,
            OfficialReportPeriodSeeder::class,
            SeptemberSchoolPlanningQuantitySeeder::class,
        ]);

        $form = app(FormTenReportService::class)->forMonth('2026-09');

        $this->assertSame(110, $form['fixture_school_count']);
        $this->assertSame('assessment_fixture', $form['quantity_source']);
        $this->assertSame(317664, $form['items']['bun']['delivered_quantity']);
        $this->assertSame(238248, $form['items']['boiled_egg']['delivered_quantity']);
        $this->assertSame(99270, $form['items']['banana']['delivered_quantity']);
        $this->assertSame(0, $form['chalan_count']);
        $this->assertDatabaseCount('deliveries', 0);

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get(route('admin.reports.form-ten.pdf', ['month' => '2026-09']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_preview_and_download_form_ten_with_missing_data_warning_outside_the_form(): void
    {
        $this->seed([DemandSetupSeeder::class, OfficialReportPeriodSeeder::class]);
        $this->school('AN-001', '11111111111', 100);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $preview = $this->actingAs($admin)->get(route('admin.reports.form-ten', ['month' => '2026-09']));
        $preview->assertOk()
            ->assertSee('Some report values are missing')
            ->assertSee('Some scheduled deliveries have not yet been entered.')
            ->assertDontSee('name="invoice_number"', false)
            ->assertSee('AN-00001')
            ->assertSee('name="bank_name"', false)
            ->assertDontSee('Invoice number has not been entered for this month.')
            ->assertSee('Official Form 10');

        $download = $this->get(route('admin.reports.form-ten.pdf', ['month' => '2026-09']));
        $download->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="form-10-2026-09.pdf"');
        $this->assertStringStartsWith('%PDF-', $download->getContent());
    }

    public function test_admin_can_save_monthly_date_and_banking_metadata_without_setting_invoice_number(): void
    {
        $this->seed(OfficialReportPeriodSeeder::class);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->put(route('admin.reports.period.update'), [
            'month' => '2026-09',
            'supplier_name' => 'স্বদেশ পল্লী লিমিটেড',
            'invoice_date' => '2026-09-30',
            'contract_number' => 'CONTRACT-SEP',
            'bank_account_name' => 'Verified Account Name',
            'bank_account_number' => '123456789',
            'bank_name' => 'Verified Bank',
            'bank_branch' => 'Anwara',
            'bank_routing_number' => '123456789',
        ])->assertRedirect(route('admin.reports.form-ten', ['month' => '2026-09']));

        $this->assertDatabaseHas('official_report_periods', [
            'month' => '2026-09',
            'bank_account_number' => '123456789',
        ]);
        $this->assertSame('2026-09-30', OfficialReportPeriod::query()->firstWhere('month', '2026-09')->invoice_date->toDateString());
    }

    public function test_invoice_numbers_are_generated_once_in_sequence_and_ignore_manual_input(): void
    {
        $this->seed(OfficialReportPeriodSeeder::class);
        $generator = app(FormTenInvoiceNumberGenerator::class);

        $first = $generator->ensureForMonth('2026-09');
        $again = $generator->ensureForMonth('2026-09');
        $second = $generator->ensureForMonth('2026-10');

        $this->assertSame('AN-00001', $first->invoice_number);
        $this->assertSame('AN-00001', $again->invoice_number);
        $this->assertSame('AN-00002', $second->invoice_number);
    }

    public function test_saving_remaining_period_details_does_not_clear_supplier_or_invoice_metadata(): void
    {
        $this->seed(OfficialReportPeriodSeeder::class);
        OfficialReportPeriod::query()->where('month', '2026-09')->update([
            'invoice_number' => 'MANUAL-OLD',
            'invoice_date' => '2026-09-30',
        ]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->put(route('admin.reports.period.update'), [
            'month' => '2026-09',
            'contract_number' => 'CONTRACT-SEP',
        ])->assertRedirect(route('admin.reports.form-ten', ['month' => '2026-09']));

        $this->assertDatabaseHas('official_report_periods', [
            'month' => '2026-09',
            'supplier_name' => 'স্বদেশ পল্লী লিমিটেড',
            'invoice_number' => 'MANUAL-OLD',
            'invoice_date' => '2026-09-30',
            'contract_number' => 'CONTRACT-SEP',
        ]);

        $this->assertSame('AN-00001', app(FormTenInvoiceNumberGenerator::class)
            ->ensureForMonth('2026-09')->invoice_number);
    }

    public function test_field_staff_cannot_open_admin_form_ten(): void
    {
        $staff = User::factory()->create(['role' => UserRole::FieldStaff]);

        $this->actingAs($staff)
            ->get(route('admin.reports.form-ten', ['month' => '2026-09']))
            ->assertForbidden();
    }

    private function school(string $code, string $emis, int $students): School
    {
        $school = School::query()->create([
            'school_code' => $code,
            'emis_code' => $emis,
            'name' => 'পরীক্ষা বিদ্যালয়',
        ]);
        SchoolStudentCount::query()->create([
            'school_id' => $school->id,
            'student_count' => $students,
            'effective_start_date' => '2026-09-01',
        ]);

        return $school;
    }

    private function delivery(School $school, User $staff, string $date, int $bun, int $egg, int $banana): void
    {
        Delivery::query()->create([
            'school_id' => $school->id,
            'date' => $date,
            'bun_quantity' => $bun,
            'egg_quantity' => $egg,
            'banana_quantity' => $banana,
            'chalan_number' => 'CH-'.$school->school_code,
            'chalan_disk' => 'local',
            'chalan_path' => 'test-chalan.jpg',
            'created_by_user_id' => $staff->id,
            'updated_by_user_id' => $staff->id,
        ]);
    }
}
