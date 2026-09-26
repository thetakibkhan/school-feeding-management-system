<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\School;
use App\Models\SchoolStudentCount;
use App\Models\User;
use Database\Seeders\DemandSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeliveryChalanFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_chalan_date_defaults_to_delivery_date_on_the_entry_form(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $school = $this->schoolWithEffectiveCount();

        $this->actingAs(User::factory()->create(['role' => UserRole::FieldStaff]))
            ->get(route('field-staff.deliveries.create', [
                'date' => '2026-09-20',
                'school_id' => $school->id,
            ]))
            ->assertOk()
            ->assertSee('type="date" name="chalan_date" value="2026-09-20"', false);
    }

    public function test_field_staff_can_save_a_physical_chalan_date_different_from_delivery_date(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $school = $this->schoolWithEffectiveCount();
        Storage::fake('delivery_photos');
        config(['filesystems.delivery_photos_disk' => 'delivery_photos']);

        $this->actingAs(User::factory()->create(['role' => UserRole::FieldStaff]))
            ->post(route('field-staff.deliveries.store'), [
                'date' => '2026-09-20',
                'chalan_date' => '2026-09-19',
                'school_id' => $school->id,
                'bun_quantity' => 90,
                'egg_quantity' => 90,
                'banana_quantity' => 0,
                'chalan_number' => 'CH-204',
                'chalan_photo' => $this->validPngUpload(),
            ])
            ->assertRedirect(route('field-staff.deliveries.index'));

        $delivery = Delivery::query()->where('school_id', $school->id)->firstOrFail();
        $this->assertSame('CH-204', $delivery->chalan_number);
        $this->assertSame('2026-09-19', $delivery->chalan_date->toDateString());
    }

    public function test_chalan_date_is_required_on_delivery_entry(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $school = $this->schoolWithEffectiveCount();
        Storage::fake('delivery_photos');
        config(['filesystems.delivery_photos_disk' => 'delivery_photos']);

        $this->actingAs(User::factory()->create(['role' => UserRole::FieldStaff]))
            ->post(route('field-staff.deliveries.store'), [
                'date' => '2026-09-20',
                'school_id' => $school->id,
                'bun_quantity' => 90,
                'egg_quantity' => 90,
                'banana_quantity' => 0,
                'chalan_number' => 'CH-205',
                'chalan_photo' => $this->validPngUpload(),
            ])
            ->assertSessionHasErrors('chalan_date');

        $this->assertSame(0, Delivery::query()->count());
    }

    public function test_chalan_date_correction_saves_the_previous_date_in_minimal_history(): void
    {
        $this->seed(DemandSetupSeeder::class);
        $fieldStaff = User::factory()->create(['role' => UserRole::FieldStaff]);
        $delivery = $this->deliveryFor($fieldStaff);

        $this->actingAs($fieldStaff)
            ->put(route('field-staff.deliveries.update', $delivery), [
                'bun_quantity' => 90,
                'egg_quantity' => 90,
                'banana_quantity' => 0,
                'chalan_number' => 'CH-206',
                'chalan_date' => '2026-09-19',
            ])
            ->assertRedirect(route('field-staff.deliveries.index'));

        $this->assertSame('CH-206', $delivery->fresh()->chalan_number);
        $this->assertSame('2026-09-19', $delivery->fresh()->chalan_date->toDateString());
        $history = $delivery->correctionHistory()->firstOrFail();
        $this->assertSame('CH-OLD', $history->previous_chalan_number);
        $this->assertSame('2026-09-20', $history->previous_chalan_date->toDateString());
    }

    private function schoolWithEffectiveCount(): School
    {
        $school = School::query()->create([
            'school_code' => 'SC-CHALAN',
            'emis_code' => 'EMIS-CHALAN',
            'name' => 'পরীক্ষা বিদ্যালয়',
        ]);

        SchoolStudentCount::query()->create([
            'school_id' => $school->id,
            'student_count' => 100,
            'effective_start_date' => '2026-09-01',
        ]);

        return $school;
    }

    private function deliveryFor(User $creator): Delivery
    {
        return Delivery::query()->create([
            'school_id' => $this->schoolWithEffectiveCount()->id,
            'date' => '2026-09-20',
            'bun_quantity' => 90,
            'egg_quantity' => 90,
            'banana_quantity' => 0,
            'chalan_number' => 'CH-OLD',
            'chalan_date' => '2026-09-20',
            'chalan_disk' => 'local',
            'chalan_path' => 'test/chalan.jpg',
            'created_by_user_id' => $creator->id,
        ]);
    }

    private function validPngUpload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'chalan.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC'),
        );
    }
}
