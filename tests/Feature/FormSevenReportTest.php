<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\FoodItem;
use App\Models\FoodSchedule;
use App\Models\OfficialReportPeriod;
use App\Models\School;
use App\Models\SchoolMonthlyPlanningQuantity;
use App\Models\SchoolStudentCount;
use App\Models\User;
use App\Services\FormSevenReportService;
use Database\Seeders\OfficialReportPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormSevenReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_school_rows_count_positive_chalans_and_sum_supplied_units(): void
    {
        $staff = User::factory()->create(['role' => UserRole::FieldStaff]);
        $first = $this->school('AN-001', '11111111111', 'প্রথম বিদ্যালয়');
        $second = $this->school('AN-002', '22222222222', 'দ্বিতীয় বিদ্যালয়');

        $this->delivery($first, $staff, '2026-06-02', 90, 0, 0);
        $this->delivery($first, $staff, '2026-06-04', 95, 100, 0);
        $this->delivery($first, $staff, '2026-06-06', 0, 100, 50);
        $this->delivery($first, $staff, '2026-05-30', 999, 999, 999);
        $this->delivery($second, $staff, '2026-06-03', 30, 40, 20);

        $form = app(FormSevenReportService::class)->forMonth('2026-06');

        $this->assertCount(2, $form['rows']);
        $this->assertSame('জুন-২৬', $form['month_label']);
        $this->assertSame('১৮৫', FormSevenReportService::bengaliDigits(185));
        $this->assertSame('প্রথম বিদ্যালয়', $form['rows'][0]['school']->name);
        $this->assertSame('actual_delivery', $form['rows'][0]['quantity_source']);
        $this->assertSame(['chalans' => 2, 'quantity' => 185], $form['rows'][0]['bun']);
        $this->assertSame(['chalans' => 2, 'quantity' => 200], $form['rows'][0]['egg']);
        $this->assertSame(['chalans' => 1, 'quantity' => 50], $form['rows'][0]['banana']);
        $this->assertSame(['chalans' => 3, 'quantity' => 215], $form['totals']['bun']);
        $this->assertSame(['chalans' => 3, 'quantity' => 240], $form['totals']['egg']);
        $this->assertSame(['chalans' => 2, 'quantity' => 70], $form['totals']['banana']);
    }

    public function test_only_admin_can_open_print_ready_official_form_seven(): void
    {
        $this->school('AN-001', '11111111111', 'প্রথম বিদ্যালয়');
        $url = '/admin/reports/form-7?month=2026-06';

        $this->get($url)->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => UserRole::FieldStaff]))
            ->get($url)->assertForbidden();

        $response = $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get($url)->assertOk();

        $html = $response->getContent();
        $this->assertIsString($html);
        $this->assertSame(6, substr_count($html, 'data-form7-page='));
        $this->assertSame(110, substr_count($html, 'data-form7-row='));
        $this->assertStringContainsString('প্রথম বিদ্যালয়', $html);
        $this->assertStringContainsString('form-7/page-1.png', $html);
        $this->assertStringContainsString('resources/css/ui.css', $html);
        $this->assertStringNotContainsString('Shortfall', $html);
        $this->assertStringNotContainsString('Entry status', $html);
        $this->assertFileExists(public_path('form-7/page-1.png'));
        $this->assertFileExists(public_path('form-7/page-6.png'));
        $this->assertFileExists(public_path('fonts/NotoSansBengali-Regular.ttf'));
    }

    public function test_invalid_month_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get('/admin/reports/form-7?month=2026-13')
            ->assertSessionHasErrors('month');
    }

    public function test_more_than_110_schools_cannot_be_silently_cut_off(): void
    {
        for ($index = 1; $index <= 111; $index++) {
            $this->school(sprintf('AN-%03d', $index), sprintf('%011d', $index), 'পরীক্ষা বিদ্যালয়');
        }

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get('/admin/reports/form-7?month=2026-06')
            ->assertSessionHasErrors('month');
    }

    public function test_form_seven_warns_about_missing_entries_but_allows_pdf_download(): void
    {
        $school = $this->school('AN-001', '11111111111', 'প্রথম বিদ্যালয়');
        $this->scheduleBunFor($school, '2026-09-02');
        OfficialReportPeriod::query()->create([
            'month' => '2026-09',
            'supplier_name' => 'স্বদেশ পল্লী লিমিটেড',
        ]);

        $preview = app(FormSevenReportService::class)->forMonth('2026-09');
        $this->assertSame(['chalans' => 0, 'quantity' => 0], $preview['totals']['bun']);
        $this->assertSame(1, $preview['missing_delivery_count']);
        $this->assertSame(0, $preview['fixture_school_count']);
        $this->assertSame([], $preview['missing_reasons']);
        $this->assertDatabaseCount('deliveries', 0);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)
            ->get('/admin/reports/form-7?month=2026-09')
            ->assertOk()
            ->assertSee('This report is generated from currently entered delivery records. Some scheduled deliveries have not yet been entered.')
            ->assertSee('Print')
            ->assertDontSee('Download PDF');

        $this->actingAs($admin)
            ->get('/admin/reports/form-7/pdf?month=2026-09')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_september_fixture_quantities_are_assumed_delivered_without_creating_chalans(): void
    {
        $school = $this->school('AN-001', '11111111111', 'প্রথম বিদ্যালয়');
        $this->scheduleBunFor($school, '2026-09-02');
        SchoolMonthlyPlanningQuantity::query()->create([
            'school_id' => $school->id,
            'month' => '2026-09',
            'bun_quantity' => 160,
            'egg_quantity' => 120,
            'banana_quantity' => 50,
            'source_document' => 'GPSFP_School List_Anwara Upazila.pdf',
        ]);

        $form = app(FormSevenReportService::class)->forMonth('2026-09');

        $this->assertSame(1, $form['fixture_school_count']);
        $this->assertSame('assessment_fixture', $form['rows'][0]['quantity_source']);
        $this->assertSame(['chalans' => 0, 'quantity' => 160], $form['rows'][0]['bun']);
        $this->assertSame(['chalans' => 0, 'quantity' => 120], $form['rows'][0]['egg']);
        $this->assertSame(['chalans' => 0, 'quantity' => 50], $form['rows'][0]['banana']);
        $this->assertDatabaseCount('deliveries', 0);

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get('/admin/reports/form-7?month=2026-09')
            ->assertOk()
            ->assertSee('assessment/demo fixture', false)
            ->assertSee('delivered = demand', false)
            ->assertSee('not Field Staff-entered transactions', false);
    }

    public function test_september_supplier_seed_is_idempotent_and_preserves_admin_edits(): void
    {
        $this->seed(OfficialReportPeriodSeeder::class);

        $period = OfficialReportPeriod::query()->where('month', '2026-09')->firstOrFail();
        $this->assertSame('স্বদেশ পল্লী লিমিটেড', $period->supplier_name);

        $period->update(['supplier_name' => 'Admin-edited supplier']);
        $this->seed(OfficialReportPeriodSeeder::class);

        $this->assertSame(
            'Admin-edited supplier',
            OfficialReportPeriod::query()->where('month', '2026-09')->value('supplier_name'),
        );
    }

    public function test_admin_can_download_form_seven_pdf_from_complete_recorded_data(): void
    {
        $school = $this->school('AN-001', '11111111111', 'প্রথম বিদ্যালয়');
        OfficialReportPeriod::query()->create([
            'month' => '2026-09',
            'supplier_name' => 'Verified September supplier',
        ]);
        $this->scheduleBunFor($school, '2026-09-02');
        $this->delivery($school, User::factory()->create(['role' => UserRole::FieldStaff]), '2026-09-02', 90, 0, 0);

        $preview = app(FormSevenReportService::class)->forMonth('2026-09');
        $this->assertSame(0, $preview['missing_delivery_count']);
        $this->assertSame(['chalans' => 0, 'quantity' => 0], $preview['totals']['egg']);

        $this->get('/admin/reports/form-7/pdf?month=2026-09')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => UserRole::FieldStaff]))
            ->get('/admin/reports/form-7/pdf?month=2026-09')
            ->assertForbidden();

        $response = $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get('/admin/reports/form-7/pdf?month=2026-09')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $content = $response->getContent();
        $this->assertIsString($content);
        $pdfHeaderOffset = strpos($content, '%PDF-1.');
        $this->assertNotFalse($pdfHeaderOffset);
        $this->assertLessThan(1024, $pdfHeaderOffset);
    }

    private function scheduleBunFor(School $school, string $date): void
    {
        SchoolStudentCount::query()->create([
            'school_id' => $school->id,
            'student_count' => 100,
            'effective_start_date' => '2026-09-01',
        ]);
        $item = FoodItem::query()->create([
            'key' => 'bun',
            'name' => 'Bun',
            'unit' => 'packets',
            'unit_weight_grams' => 120,
        ]);
        $schedule = FoodSchedule::query()->create(['date' => $date]);
        $schedule->items()->attach($item);
    }

    private function school(string $code, string $emis, string $name): School
    {
        return School::query()->create([
            'school_code' => $code,
            'emis_code' => $emis,
            'name' => $name,
        ]);
    }

    private function delivery(School $school, User $staff, string $date, int $bun, int $egg, int $banana): void
    {
        Delivery::query()->create([
            'school_id' => $school->id,
            'date' => $date,
            'bun_quantity' => $bun,
            'egg_quantity' => $egg,
            'banana_quantity' => $banana,
            'chalan_disk' => 'local',
            'chalan_path' => 'test-chalan.jpg',
            'created_by_user_id' => $staff->id,
            'updated_by_user_id' => $staff->id,
        ]);
    }
}
