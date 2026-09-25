<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\FoodItem;
use App\Models\FoodSchedule;
use App\Models\NonWorkingDate;
use App\Models\RationSetting;
use App\Models\School;
use App\Models\SchoolStudentCount;
use App\Models\User;
use App\Services\DemandCalculator;
use Database\Seeders\DemandSetupSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DemandSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_demand_setup_seeds_initial_rations_and_exact_september_totals(): void
    {
        $this->seed(DemandSetupSeeder::class);

        $this->assertDatabaseHas('ration_settings', [
            'food_item_id' => FoodItem::query()->where('key', 'bun')->value('id'),
            'ration_grams' => 120,
            'effective_start_date' => '2026-09-01 00:00:00',
        ]);
        $this->assertDatabaseHas('ration_settings', [
            'food_item_id' => FoodItem::query()->where('key', 'boiled_egg')->value('id'),
            'ration_grams' => 60,
        ]);
        $this->assertDatabaseHas('ration_settings', [
            'food_item_id' => FoodItem::query()->where('key', 'banana')->value('id'),
            'ration_grams' => 100,
        ]);

        $this->assertSame(16, $this->scheduledDayCount('bun'));
        $this->assertSame(12, $this->scheduledDayCount('boiled_egg'));
        $this->assertSame(5, $this->scheduledDayCount('banana'));
        $this->assertDatabaseHas('food_schedules', ['date' => '2026-09-04 00:00:00']);
        $this->assertDatabaseHas('non_working_dates', ['date' => '2026-09-22 00:00:00']);
    }

    public function test_demand_uses_the_ration_and_student_count_effective_on_the_requested_date(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $school = $this->schoolWithCount(101, '2026-09-01');
        $bun = FoodItem::query()->where('key', 'bun')->firstOrFail();

        RationSetting::query()->create([
            'food_item_id' => $bun->id,
            'ration_grams' => 150,
            'effective_start_date' => '2026-09-15',
        ]);
        SchoolStudentCount::query()->create([
            'school_id' => $school->id,
            'student_count' => 120,
            'effective_start_date' => '2026-09-15',
        ]);

        $calculator = app(DemandCalculator::class);

        $beforeChange = $calculator->forSchoolDateItem($school, '2026-09-14', $bun);
        $afterChange = $calculator->forSchoolDateItem($school, '2026-09-15', $bun);

        $this->assertSame(91, $beforeChange->quantity);
        $this->assertSame(120, $beforeChange->rationGrams);
        $this->assertSame(108, $afterChange->quantity);
        $this->assertSame(150, $afterChange->rationGrams);
    }

    public function test_non_working_and_unscheduled_dates_have_zero_demand(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $school = $this->schoolWithCount(100, '2026-09-01');
        $calculator = app(DemandCalculator::class);

        $this->assertSame(0, $calculator->forSchoolDateItem(
            $school,
            '2026-09-22',
            FoodItem::query()->where('key', 'bun')->firstOrFail(),
        )->quantity);
        $this->assertSame(0, $calculator->forSchoolDateItem(
            $school,
            '2026-09-01',
            FoodItem::query()->where('key', 'bun')->firstOrFail(),
        )->quantity);
        $this->assertSame(90, $calculator->forSchoolDateItem(
            $school,
            '2026-09-01',
            FoodItem::query()->where('key', 'banana')->firstOrFail(),
        )->quantity);
    }

    public function test_a_school_without_an_effective_student_count_has_no_demand(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $school = $this->schoolWithCount(100, '2026-09-02');

        $result = app(DemandCalculator::class)->forSchoolDateItem(
            $school,
            '2026-09-01',
            FoodItem::query()->where('key', 'banana')->firstOrFail(),
        );

        $this->assertSame(0, $result->quantity);
        $this->assertNull($result->studentCount);
    }

    public function test_admin_cannot_save_an_empty_working_schedule(): void
    {
        $response = $this->actingAs($this->admin())
            ->from('/admin/demand-setup')
            ->post('/admin/demand-setup/schedules', [
                'date' => '2026-10-01',
                'food_item_ids' => [],
            ]);

        $response->assertRedirect('/admin/demand-setup');
        $response->assertSessionHasErrors('food_item_ids');
    }

    public function test_schedule_and_non_working_date_changes_are_blocked_after_delivery_exists(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $schedule = FoodSchedule::query()->whereDate('date', '2026-09-01')->firstOrFail();
        $holiday = NonWorkingDate::query()->whereDate('date', '2026-09-22')->firstOrFail();
        $this->createDeliveryTable();
        DB::table('deliveries')->insert([
            'date' => '2026-09-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('deliveries')->insert([
            'date' => '2026-09-22',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->from('/admin/demand-setup')
            ->delete('/admin/demand-setup/schedules/'.$schedule->id)
            ->assertRedirect('/admin/demand-setup')
            ->assertSessionHas('error');

        $this->actingAs($this->admin())
            ->from('/admin/demand-setup')
            ->delete('/admin/demand-setup/non-working-dates/'.$holiday->id)
            ->assertRedirect('/admin/demand-setup')
            ->assertSessionHas('error');
    }

    public function test_normal_seeder_rerun_does_not_overwrite_an_admin_edited_schedule(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $schedule = FoodSchedule::query()->whereDate('date', '2026-09-01')->firstOrFail();
        $bun = FoodItem::query()->where('key', 'bun')->firstOrFail();

        $schedule->items()->sync([$bun->id]);

        $this->seed(DemandSetupSeeder::class);

        $this->assertSame(['bun'], $schedule->fresh()->items()->pluck('key')->all());
    }

    public function test_field_staff_cannot_manage_demand_setup(): void
    {
        $fieldStaff = User::factory()->create(['role' => UserRole::FieldStaff]);

        $this->actingAs($fieldStaff)->get('/admin/demand-setup')->assertForbidden();
    }

    private function scheduledDayCount(string $itemKey): int
    {
        return FoodSchedule::query()
            ->whereHas('items', fn ($query) => $query->where('key', $itemKey))
            ->count();
    }

    private function schoolWithCount(int $studentCount, string $effectiveDate): School
    {
        $school = School::query()->create([
            'school_code' => 'SC-'.fake()->unique()->numerify('###'),
            'emis_code' => 'EMIS-'.fake()->unique()->numerify('#####'),
            'name' => 'পরীক্ষা বিদ্যালয়',
        ]);

        SchoolStudentCount::query()->create([
            'school_id' => $school->id,
            'student_count' => $studentCount,
            'effective_start_date' => $effectiveDate,
        ]);

        return $school;
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    private function createDeliveryTable(): void
    {
        Schema::create('deliveries', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->timestamps();
        });
    }
}
