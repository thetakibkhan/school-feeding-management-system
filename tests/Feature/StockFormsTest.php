<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\FoodSchedule;
use App\Models\School;
use App\Models\SchoolMonthlyStockInput;
use App\Models\SchoolStudentCount;
use App\Models\StockReportPeriod;
use App\Models\User;
use App\Services\StockReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockFormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_stock_keeps_opening_receipts_and_distribution_separate(): void
    {
        $school = $this->school('AN-001', '91411060101', 'পরীক্ষা বিদ্যালয়');
        $this->delivery($school, 10, 4, 2);
        SchoolMonthlyStockInput::query()->create(array_merge($this->zeroStock($school->id), [
            'bun_opening' => 3,
            'bun_distributed' => 8,
            'egg_distributed' => 4,
            'banana_opening' => 2,
            'banana_distributed' => 3,
        ]));

        $report = app(StockReportService::class)->forMonth('2026-09');
        $page = $report['schools'][0];

        $this->assertSame(13, $page['items']['bun']['available']);
        $this->assertSame(10, $page['items']['bun']['received']);
        $this->assertSame(8, $page['items']['bun']['distributed']);
        $this->assertSame(5, $page['items']['bun']['closing']);
        $this->assertSame(0, $page['items']['egg']['closing']);
        $this->assertSame(1, $page['items']['banana']['closing']);
        $this->assertSame(0, $page['items']['biscuit']['closing']);
        $this->assertTrue($page['complete']);
    }

    public function test_upazila_totals_use_only_complete_school_stock_records(): void
    {
        $first = $this->school('AN-001', '91411060101', 'প্রথম বিদ্যালয়');
        $second = $this->school('AN-002', '91411060102', 'দ্বিতীয় বিদ্যালয়');
        $this->delivery($first, 10, 4, 2);
        SchoolMonthlyStockInput::query()->create(array_merge($this->zeroStock($first->id), [
            'bun_opening' => 3,
            'bun_distributed' => 8,
        ]));
        SchoolMonthlyStockInput::query()->create(array_merge($this->zeroStock($second->id), [
            'bun_opening' => 4,
            'bun_distributed' => 1,
        ]));

        $report = app(StockReportService::class)->forMonth('2026-09');

        $this->assertSame(17, $report['totals']['bun']['available']);
        $this->assertSame(9, $report['totals']['bun']['distributed']);
        $this->assertSame(8, $report['totals']['bun']['closing']);
        $this->assertSame(0, $report['totals']['biscuit']['available']);
        $this->assertSame(2, $report['complete_school_count']);
    }

    public function test_missing_stock_facts_are_not_treated_as_zero(): void
    {
        $this->school('AN-001', '91411060101', 'পরীক্ষা বিদ্যালয়');

        $report = app(StockReportService::class)->forMonth('2026-09');

        $this->assertNull($report['schools'][0]['items']['bun']['distributed']);
        $this->assertNull($report['schools'][0]['items']['bun']['closing']);
        $this->assertNull($report['totals']['bun']['closing']);
        $this->assertSame(0, $report['complete_school_count']);
    }

    public function test_missing_scheduled_delivery_is_not_a_confirmed_zero_receipt(): void
    {
        $school = $this->school('AN-001', '91411060101', 'পরীক্ষা বিদ্যালয়');
        FoodSchedule::query()->create(['date' => '2026-09-02']);
        SchoolMonthlyStockInput::query()->create($this->zeroStock($school->id));

        $report = app(StockReportService::class)->forMonth('2026-09');

        $this->assertSame(1, $report['schools'][0]['missing_delivery_count']);
        $this->assertNull($report['schools'][0]['items']['bun']['received']);
        $this->assertFalse($report['schools'][0]['complete']);
    }

    public function test_form_period_metadata_is_saved_separately(): void
    {
        StockReportPeriod::query()->create([
            'form_type' => 'form_12', 'month' => '2026-09',
            'district_name' => 'চট্টগ্রাম', 'upazila_name' => 'আনোয়ারা',
        ]);
        StockReportPeriod::query()->create([
            'form_type' => 'form_13', 'month' => '2026-09',
            'district_name' => 'চট্টগ্রাম', 'upazila_name' => 'আনোয়ারা',
            'supplier_name' => 'September supplier',
        ]);

        $this->assertSame(2, StockReportPeriod::query()->where('month', '2026-09')->count());
        $this->assertNull(StockReportPeriod::query()->where('form_type', 'form_12')->value('supplier_name'));
    }

    public function test_admin_can_save_reopen_and_preview_each_form(): void
    {
        $school = $this->school('AN-001', '91411060101', 'দীর্ঘ নামের সরকারি প্রাথমিক বিদ্যালয়');
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        foreach (['12', '13'] as $form) {
            $this->actingAs($admin)->get(route('admin.reports.stock.information', ['form' => $form, 'month' => '2026-09']))->assertOk();
            $this->put(route('admin.reports.stock.information.update', ['form' => $form]), [
                'month' => '2026-09', 'district_name' => 'চট্টগ্রাম',
                'upazila_name' => 'আনোয়ারা', 'supplier_name' => 'September supplier',
            ])->assertRedirect();
            $this->put(route('admin.reports.stock.schools.update', ['form' => $form, 'school' => $school]), $this->zeroStock($school->id))
                ->assertRedirect();
            $information = $this->get(route('admin.reports.stock.information', ['form' => $form, 'month' => '2026-09']))->assertOk();
            if ($form === '13') {
                $information->assertSee('September supplier', false);
            }
            $this->get(route('admin.reports.stock.preview', ['form' => $form, 'month' => '2026-09']))
                ->assertOk()->assertSee('official-page');
        }

        $this->assertSame(2, StockReportPeriod::query()->where('month', '2026-09')->count());
        $this->assertSame(1, SchoolMonthlyStockInput::query()->where('month', '2026-09')->count());
    }

    public function test_missing_stock_blocks_final_pdf_and_field_staff_cannot_open_forms(): void
    {
        $this->school('AN-001', '91411060101', 'পরীক্ষা বিদ্যালয়');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        StockReportPeriod::query()->create([
            'form_type' => 'form_13', 'month' => '2026-09',
            'district_name' => 'চট্টগ্রাম', 'upazila_name' => 'আনোয়ারা', 'supplier_name' => 'Supplier',
        ]);

        $this->actingAs($admin)->get(route('admin.reports.stock.preview', ['form' => 13, 'month' => '2026-09']))
            ->assertOk()->assertSee('incomplete');
        $this->get(route('admin.reports.stock.pdf', ['form' => 13, 'month' => '2026-09']))
            ->assertSessionHasErrors('report');

        $staff = User::factory()->create(['role' => UserRole::FieldStaff]);
        $this->actingAs($staff)->get(route('admin.reports.stock.information', ['form' => 13]))->assertForbidden();
    }

    public function test_form_thirteen_needs_only_its_three_food_items(): void
    {
        $school = $this->school('AN-001', '91411060101', 'পরীক্ষা বিদ্যালয়');
        StockReportPeriod::query()->create([
            'form_type' => 'form_13', 'month' => '2026-09',
            'district_name' => 'চট্টগ্রাম', 'upazila_name' => 'আনোয়ারা', 'supplier_name' => 'Supplier',
        ]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->put(route('admin.reports.stock.schools.update', ['form' => 13, 'school' => $school]), [
            'month' => '2026-09',
            'bun_opening' => 0, 'bun_distributed' => 0,
            'egg_opening' => 0, 'egg_distributed' => 0,
            'banana_opening' => 0, 'banana_distributed' => 0,
        ])->assertRedirect();

        $this->get(route('admin.reports.stock.pdf', ['form' => 13, 'month' => '2026-09']))->assertOk();
        $this->get(route('admin.reports.stock.pdf', ['form' => 12, 'month' => '2026-09']))->assertSessionHasErrors('report');
    }

    public function test_completed_forms_download_valid_a4_pdfs_with_expected_page_counts(): void
    {
        $school = $this->school('AN-001', '91411060101', 'পরীক্ষা বিদ্যালয়');
        SchoolStudentCount::query()->create(['school_id' => $school->id, 'student_count' => 1, 'effective_start_date' => '2026-09-01']);
        SchoolMonthlyStockInput::query()->create(array_merge($this->zeroStock($school->id), ['boy_count' => 1, 'girl_count' => 0]));
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        foreach (['12', '13'] as $form) {
            StockReportPeriod::query()->create([
                'form_type' => 'form_'.$form, 'month' => '2026-09',
                'district_name' => 'চট্টগ্রাম', 'upazila_name' => 'আনোয়ারা',
                'supplier_name' => $form === '13' ? 'Supplier' : null,
            ]);
            $response = $this->actingAs($admin)->get(route('admin.reports.stock.pdf', ['form' => $form, 'month' => '2026-09']));
            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }
    }

    private function school(string $code, string $emis, string $name): School
    {
        return School::query()->create(['school_code' => $code, 'emis_code' => $emis, 'name' => $name]);
    }

    private function delivery(School $school, int $bun, int $egg, int $banana): void
    {
        Delivery::query()->create([
            'school_id' => $school->id,
            'date' => '2026-09-02',
            'bun_quantity' => $bun,
            'egg_quantity' => $egg,
            'banana_quantity' => $banana,
            'chalan_number' => 'CH-1',
            'chalan_date' => '2026-09-02',
            'chalan_disk' => 'local',
            'chalan_path' => 'test/chalan.png',
            'created_by_user_id' => User::factory()->create(['role' => UserRole::FieldStaff])->id,
        ]);
    }

    /** @return array<string, int|string> */
    private function zeroStock(int $schoolId): array
    {
        return [
            'school_id' => $schoolId, 'month' => '2026-09',
            'bun_opening' => 0, 'bun_distributed' => 0,
            'egg_opening' => 0, 'egg_distributed' => 0,
            'banana_opening' => 0, 'banana_distributed' => 0,
            'biscuit_opening' => 0, 'biscuit_received' => 0, 'biscuit_distributed' => 0,
            'milk_opening' => 0, 'milk_received' => 0, 'milk_distributed' => 0,
        ];
    }
}
