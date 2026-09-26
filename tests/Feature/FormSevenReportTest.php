<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\School;
use App\Models\User;
use App\Services\FormSevenReportService;
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
