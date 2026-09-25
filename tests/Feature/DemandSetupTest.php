<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\FoodItem;
use App\Models\FoodSchedule;
use App\Models\NonWorkingDate;
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

    public function test_demand_setup_seeds_item_weights_and_source_distribution_totals_without_calendar_dates(): void
    {
        $this->seed(DemandSetupSeeder::class);

        $this->assertSame(120, FoodItem::query()->where('key', 'bun')->value('unit_weight_grams'));
        $this->assertSame(60, FoodItem::query()->where('key', 'boiled_egg')->value('unit_weight_grams'));
        $this->assertSame(100, FoodItem::query()->where('key', 'banana')->value('unit_weight_grams'));
        $this->assertSame([
            'bun' => 16,
            'boiled_egg' => 12,
            'banana' => 5,
        ], DemandSetupSeeder::SOURCE_DISTRIBUTION_DAY_TOTALS);
        $this->assertSame(0, FoodSchedule::query()->count());
        $this->assertSame(0, NonWorkingDate::query()->count());
    }

    public function test_demand_uses_effective_students_and_unit_weight_is_metadata_only(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $school = $this->schoolWithCount(101, '2026-09-01');
        $bun = FoodItem::query()->where('key', 'bun')->firstOrFail();
        $oldDateSchedule = FoodSchedule::query()->create(['date' => '2026-09-14']);
        $oldDateSchedule->items()->sync([$bun->id]);
        $schedule = FoodSchedule::query()->create(['date' => '2026-10-01']);
        $schedule->items()->sync([$bun->id]);
        SchoolStudentCount::query()->create([
            'school_id' => $school->id,
            'student_count' => 120,
            'effective_start_date' => '2026-09-15',
        ]);

        $calculator = app(DemandCalculator::class);

        $beforeStudentCountChange = $calculator->forSchoolDateItem($school, '2026-09-14', $bun);
        $result = $calculator->forSchoolDateItem($school, '2026-10-01', $bun);

        $this->assertSame(91, $beforeStudentCountChange->quantity);
        $this->assertSame(108, $result->quantity);
        $this->assertSame(120, $result->studentCount);
        $this->assertSame(120, $result->unitWeightGrams);
    }

    public function test_non_working_and_unscheduled_dates_have_zero_demand(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $school = $this->schoolWithCount(100, '2026-09-01');
        $banana = FoodItem::query()->where('key', 'banana')->firstOrFail();
        $schedule = FoodSchedule::query()->create(['date' => '2026-10-01']);
        $schedule->items()->sync([$banana->id]);
        NonWorkingDate::query()->create([
            'date' => '2026-10-02',
            'reason' => 'Test non-working date',
        ]);
        $calculator = app(DemandCalculator::class);

        $this->assertSame(0, $calculator->forSchoolDateItem(
            $school,
            '2026-10-02',
            FoodItem::query()->where('key', 'bun')->firstOrFail(),
        )->quantity);
        $this->assertSame(0, $calculator->forSchoolDateItem(
            $school,
            '2026-10-01',
            FoodItem::query()->where('key', 'bun')->firstOrFail(),
        )->quantity);
        $this->assertSame(90, $calculator->forSchoolDateItem(
            $school,
            '2026-10-01',
            $banana,
        )->quantity);
    }

    public function test_a_school_without_an_effective_student_count_has_no_demand(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $school = $this->schoolWithCount(100, '2026-09-02');

        $result = app(DemandCalculator::class)->forSchoolDateItem(
            $school,
            '2026-10-01',
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

    public function test_admin_can_configure_real_calendar_dates_and_edit_unit_weight(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $admin = $this->admin();
        $bun = FoodItem::query()->where('key', 'bun')->firstOrFail();
        $banana = FoodItem::query()->where('key', 'banana')->firstOrFail();

        $this->actingAs($admin)
            ->post('/admin/demand-setup/schedules', [
                'date' => '2026-10-05',
                'food_item_ids' => [$banana->id],
            ])
            ->assertRedirect('/admin/demand-setup');

        $this->assertDatabaseHas('food_schedules', ['date' => '2026-10-05 00:00:00']);
        $this->assertDatabaseHas('food_schedule_items', [
            'food_schedule_id' => FoodSchedule::query()->whereDate('date', '2026-10-05')->value('id'),
            'food_item_id' => $banana->id,
        ]);

        $this->actingAs($admin)
            ->put('/admin/demand-setup/items/'.$bun->id.'/specification', [
                'unit_weight_grams' => 125,
            ])
            ->assertRedirect('/admin/demand-setup');

        $this->assertDatabaseHas('food_items', [
            'id' => $bun->id,
            'unit_weight_grams' => 125,
        ]);

        $bun->refresh();
        $school = $this->schoolWithCount(100, '2026-09-01');
        $bunSchedule = FoodSchedule::query()->create(['date' => '2026-10-06']);
        $bunSchedule->items()->sync([$bun->id]);
        $demand = app(DemandCalculator::class)->forSchoolDateItem($school, '2026-10-06', $bun);

        $this->assertSame(90, $demand->quantity);
        $this->assertSame(125, $demand->unitWeightGrams);
    }

    public function test_schedule_and_non_working_date_changes_are_blocked_after_delivery_exists(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $schedule = FoodSchedule::query()->create(['date' => '2026-10-01']);
        $schedule->items()->sync([FoodItem::query()->where('key', 'bun')->value('id')]);
        $holiday = NonWorkingDate::query()->create([
            'date' => '2026-10-02',
            'reason' => 'Test non-working date',
        ]);
        $this->createDeliveryTable();
        DB::table('deliveries')->insert([
            'date' => '2026-10-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('deliveries')->insert([
            'date' => '2026-10-02',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $banana = FoodItem::query()->where('key', 'banana')->firstOrFail();

        $this->actingAs($this->admin())
            ->from('/admin/demand-setup')
            ->put('/admin/demand-setup/schedules/'.$schedule->id, [
                'date' => '2026-10-01',
                'food_item_ids' => [$banana->id],
            ])
            ->assertRedirect('/admin/demand-setup')
            ->assertSessionHas('error');

        $this->actingAs($this->admin())
            ->from('/admin/demand-setup')
            ->put('/admin/demand-setup/non-working-dates/'.$holiday->id, [
                'date' => '2026-10-02',
                'reason' => 'Updated reason',
            ])
            ->assertRedirect('/admin/demand-setup')
            ->assertSessionHas('error');

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
        $schedule = FoodSchedule::query()->create(['date' => '2026-10-01']);
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
