<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\FoodItem;
use App\Models\FoodSchedule;
use App\Models\NonWorkingDate;
use App\Models\School;
use App\Models\SchoolStudentCount;
use App\Models\User;
use App\Services\DailyDeliveryReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DailyDeliveryReportTest extends TestCase
{
    use RefreshDatabase;

    private const REPORT_DATE = '2026-10-05';

    public function test_working_report_uses_effective_student_counts_and_calculates_each_item_and_upazila_totals(): void
    {
        $items = $this->foodItems();
        $this->schedule(self::REPORT_DATE, [$items['bun'], $items['boiled_egg']]);
        $firstSchool = $this->school('AN-001', 101, '2026-10-01');
        $this->school('AN-002', 100, '2026-10-01');
        $this->school('AN-003', 200, '2026-10-06');
        $this->delivery($firstSchool, $this->fieldStaff(), 80, 100, 3);

        $report = app(DailyDeliveryReportService::class)->forDate(self::REPORT_DATE);

        $this->assertSame('working', $report['status']);
        $this->assertCount(2, $report['rows']);
        $this->assertSame('AN-001', $report['rows'][0]['school']->school_code);
        $this->assertSame(['demand' => 91, 'delivered' => 80, 'shortfall' => 11, 'excess' => 0], $report['rows'][0]['items']['bun']);
        $this->assertSame(['demand' => 91, 'delivered' => 100, 'shortfall' => 0, 'excess' => 9], $report['rows'][0]['items']['boiled_egg']);
        $this->assertSame(['demand' => 0, 'delivered' => 3, 'shortfall' => 0, 'excess' => 3], $report['rows'][0]['items']['banana']);
        $this->assertFalse($report['rows'][1]['has_delivery']);
        $this->assertSame(['demand' => 181, 'delivered' => 80, 'shortfall' => 101, 'excess' => 0], $report['totals']['bun']);
        $this->assertSame(['demand' => 181, 'delivered' => 100, 'shortfall' => 90, 'excess' => 9], $report['totals']['boiled_egg']);
        $this->assertSame(['demand' => 0, 'delivered' => 3, 'shortfall' => 0, 'excess' => 3], $report['totals']['banana']);
        $this->assertCount(2, $report['shortfallRows']);
    }

    public function test_off_day_and_unconfigured_date_do_not_show_school_rows(): void
    {
        $this->foodItems();
        $this->school('AN-001', 100, '2026-10-01');
        NonWorkingDate::query()->create(['date' => '2026-10-06', 'reason' => 'Holiday']);

        $offDay = app(DailyDeliveryReportService::class)->forDate('2026-10-06');
        $notSetUp = app(DailyDeliveryReportService::class)->forDate('2026-10-07');

        $this->assertSame('off_day', $offDay['status']);
        $this->assertSame([], $offDay['rows']);
        $this->assertSame(0, $offDay['totals']['bun']['demand']);
        $this->assertSame('not_set_up', $notSetUp['status']);
        $this->assertSame([], $notSetUp['rows']);
    }

    public function test_admin_and_field_staff_can_view_and_export_the_same_daily_report(): void
    {
        $items = $this->foodItems();
        $this->schedule(self::REPORT_DATE, [$items['bun']]);
        $this->school('AN-001', 100, '2026-10-01');

        $this->get('/reports/daily-delivery?date='.self::REPORT_DATE)->assertRedirect('/login');

        $this->actingAs($this->admin())
            ->get('/reports/daily-delivery?date='.self::REPORT_DATE)
            ->assertOk()
            ->assertSee('পরীক্ষা বিদ্যালয়')
            ->assertSee('No entry yet')
            ->assertSee('Upazila total');

        $this->actingAs($this->fieldStaff())
            ->get('/reports/daily-delivery?date='.self::REPORT_DATE)
            ->assertOk()
            ->assertSee('পরীক্ষা বিদ্যালয়')
            ->assertSee('No entry yet');

        $export = $this->get('/reports/daily-delivery/export?date='.self::REPORT_DATE);
        $export->assertOk()->assertDownload('daily-delivery-'.self::REPORT_DATE.'.csv');
        $content = $export->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('পরীক্ষা বিদ্যালয়', $content);
        $this->assertStringContainsString('AN-001', $content);
        $this->assertStringContainsString('90,0,90,0', $content);
    }

    public function test_admin_sees_chalan_photo_links_on_daily_report_and_field_staff_does_not(): void
    {
        $items = $this->foodItems();
        $this->schedule(self::REPORT_DATE, [$items['bun']]);
        $school = $this->school('AN-001', 100, '2026-10-01');
        $delivery = $this->delivery($school, $this->fieldStaff(), 80, 0, 0);
        $photoUrl = route('admin.deliveries.chalan', $delivery);

        $this->actingAs($this->admin())
            ->get('/reports/daily-delivery?date='.self::REPORT_DATE)
            ->assertOk()
            ->assertSee('Chalan photo')
            ->assertSee($photoUrl, false)
            ->assertSee('data-photo-modal', false);

        $this->actingAs($this->fieldStaff())
            ->get('/reports/daily-delivery?date='.self::REPORT_DATE)
            ->assertOk()
            ->assertDontSee('Chalan photo')
            ->assertDontSee($photoUrl, false)
            ->assertDontSee('data-photo-modal', false);
    }

    public function test_dashboard_uses_today_and_field_staff_see_only_their_own_entries(): void
    {
        $this->travelTo(Carbon::parse(self::REPORT_DATE.' 10:00:00'));
        $items = $this->foodItems();
        $this->schedule(self::REPORT_DATE, [$items['bun']]);
        $firstSchool = $this->school('AN-001', 100, '2026-10-01');
        $secondSchool = $this->school('AN-002', 100, '2026-10-01');
        $firstStaff = $this->fieldStaff();
        $secondStaff = $this->fieldStaff();
        $this->delivery($firstSchool, $firstStaff, 50, 0, 0);
        $this->delivery($secondSchool, $secondStaff, 90, 0, 0);

        $this->actingAs($this->admin())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Schools with a shortfall')
            ->assertSee('AN-001')
            ->assertDontSee('AN-002');

        $this->actingAs($firstStaff)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('AN-001')
            ->assertDontSee('AN-002');
    }

    public function test_dashboard_school_lists_are_paginated_in_groups_of_ten(): void
    {
        $this->travelTo(Carbon::parse(self::REPORT_DATE.' 10:00:00'));
        $items = $this->foodItems();
        $this->schedule(self::REPORT_DATE, [$items['bun']]);
        $staff = $this->fieldStaff();

        for ($index = 1; $index <= 12; $index++) {
            $school = $this->school(sprintf('AN-%03d', $index), 100, '2026-10-01');
            $this->delivery($school, $staff, 0, 0, 0);
        }

        $this->actingAs($this->admin())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('AN-001')
            ->assertSee('AN-010')
            ->assertDontSee('AN-011')
            ->assertSee('shortfalls=2');

        $this->actingAs($this->admin())
            ->get('/dashboard?shortfalls=2')
            ->assertOk()
            ->assertSee('AN-011')
            ->assertSee('AN-012')
            ->assertDontSee('AN-001');

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('AN-001')
            ->assertSee('AN-010')
            ->assertDontSee('AN-011')
            ->assertSee('entries=2');

        $this->actingAs($staff)
            ->get('/dashboard?entries=2')
            ->assertOk()
            ->assertSee('AN-011')
            ->assertSee('AN-012')
            ->assertDontSee('AN-001');
    }

    public function test_dashboard_labels_off_day_and_unconfigured_today(): void
    {
        $this->foodItems();
        $admin = $this->admin();
        NonWorkingDate::query()->create(['date' => '2026-10-06', 'reason' => 'Holiday']);

        $this->travelTo(Carbon::parse('2026-10-06 10:00:00'));
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Off-day');

        $this->travelTo(Carbon::parse('2026-10-07 10:00:00'));
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Date not set up');
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    /** @return array<string, FoodItem> */
    private function foodItems(): array
    {
        return [
            'bun' => FoodItem::query()->create(['key' => 'bun', 'name' => 'Bun', 'unit' => 'packets', 'unit_weight_grams' => 120]),
            'boiled_egg' => FoodItem::query()->create(['key' => 'boiled_egg', 'name' => 'Boiled Egg', 'unit' => 'pieces', 'unit_weight_grams' => 60]),
            'banana' => FoodItem::query()->create(['key' => 'banana', 'name' => 'Banana', 'unit' => 'pieces', 'unit_weight_grams' => 100]),
        ];
    }

    /** @param list<FoodItem> $items */
    private function schedule(string $date, array $items): void
    {
        $schedule = FoodSchedule::query()->create(['date' => $date]);
        $schedule->items()->sync(array_map(fn (FoodItem $item): int => $item->id, $items));
    }

    private function school(string $code, int $studentCount, string $effectiveDate): School
    {
        $school = School::query()->create([
            'school_code' => $code,
            'emis_code' => 'EMIS-'.$code,
            'name' => 'পরীক্ষা বিদ্যালয়',
        ]);

        SchoolStudentCount::query()->create([
            'school_id' => $school->id,
            'student_count' => $studentCount,
            'effective_start_date' => $effectiveDate,
        ]);

        return $school;
    }

    private function delivery(School $school, User $creator, int $bun, int $egg, int $banana): Delivery
    {
        return Delivery::query()->create([
            'school_id' => $school->id,
            'date' => self::REPORT_DATE,
            'bun_quantity' => $bun,
            'egg_quantity' => $egg,
            'banana_quantity' => $banana,
            'chalan_disk' => 'local',
            'chalan_path' => 'test-chalan.jpg',
            'created_by_user_id' => $creator->id,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    private function fieldStaff(): User
    {
        return User::factory()->create(['role' => UserRole::FieldStaff]);
    }
}
