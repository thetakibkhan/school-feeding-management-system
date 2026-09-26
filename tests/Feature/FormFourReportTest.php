<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\FoodItem;
use App\Models\FoodSchedule;
use App\Models\School;
use App\Models\SchoolStudentCount;
use App\Models\User;
use App\Services\FormFourReportService;
use Database\Seeders\DemandSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormFourReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_four_uses_recorded_chalan_number_and_date_and_keeps_missing_dates_distinct(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $school = School::query()->create([
            'school_code' => 'AN-001',
            'emis_code' => 'EMIS-001',
            'name' => 'পরীক্ষা বিদ্যালয়',
        ]);
        SchoolStudentCount::query()->create([
            'school_id' => $school->id,
            'student_count' => 100,
            'effective_start_date' => '2026-09-01',
        ]);
        $creator = User::factory()->create(['role' => UserRole::FieldStaff]);
        Delivery::query()->create([
            'school_id' => $school->id,
            'date' => '2026-09-02',
            'chalan_number' => 'CH-SEP-02',
            'chalan_date' => '2026-09-01',
            'bun_quantity' => 91,
            'egg_quantity' => 90,
            'banana_quantity' => 0,
            'chalan_disk' => 'local',
            'chalan_path' => 'test/chalan.jpg',
            'created_by_user_id' => $creator->id,
        ]);

        $report = app(FormFourReportService::class)->forMonth('2026-09');
        $page = $report['schools'][0];

        $this->assertSame(30, count($page['daily_rows']));
        $this->assertTrue($page['daily_rows'][1]['entry_recorded']);
        $this->assertSame('CH-SEP-02', $page['daily_rows'][1]['chalan_number']);
        $this->assertSame('2026-09-01', $page['daily_rows'][1]['chalan_date']);
        $this->assertSame(91, $page['daily_rows'][1]['bun_quantity']);
        $this->assertFalse($page['daily_rows'][0]['entry_recorded']);
        $this->assertSame(20, $page['missing_scheduled_entry_count']);
        $this->assertSame(91, $page['totals']['bun']);
        $this->assertSame(90, $page['totals']['boiled_egg']);
        $this->assertSame(0, $page['totals']['banana']);
    }

    public function test_form_four_preview_is_an_admin_only_official_form_preview(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $school = School::query()->create([
            'school_code' => 'AN-001',
            'emis_code' => 'EMIS-001',
            'name' => 'পরীক্ষা বিদ্যালয়',
        ]);
        SchoolStudentCount::query()->create([
            'school_id' => $school->id,
            'student_count' => 100,
            'effective_start_date' => '2026-09-01',
        ]);

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get(route('admin.reports.form-four', ['month' => '2026-09']))
            ->assertOk()
            ->assertSee('Official Form 4')
            ->assertSee('2026-09-01')
            ->assertSee('Download PDF');

        $this->actingAs(User::factory()->create(['role' => UserRole::FieldStaff]))
            ->get(route('admin.reports.form-four', ['month' => '2026-09']))
            ->assertForbidden();
    }
}
