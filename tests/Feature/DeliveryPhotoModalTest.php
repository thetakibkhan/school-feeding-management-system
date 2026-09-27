<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryPhotoModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_list_opens_chalan_photos_in_an_accessible_modal(): void
    {
        $creator = User::factory()->create(['role' => UserRole::FieldStaff]);
        $delivery = $this->deliveryFor($creator);

        $this->actingAs($creator)
            ->get('/field-staff/deliveries')
            ->assertOk()
            ->assertSee('data-photo-open', false)
            ->assertSee(route('field-staff.deliveries.chalan', $delivery), false)
            ->assertSee('<dialog', false)
            ->assertSee('data-photo-modal-image', false)
            ->assertDontSee('target="_blank"', false);
    }

    public function test_delivery_correction_page_opens_current_chalan_in_the_same_modal_pattern(): void
    {
        $creator = User::factory()->create(['role' => UserRole::FieldStaff]);
        $delivery = $this->deliveryFor($creator);

        $this->actingAs($creator)
            ->get('/field-staff/deliveries/'.$delivery->id.'/edit')
            ->assertOk()
            ->assertSee('data-photo-open', false)
            ->assertSee('<dialog', false)
            ->assertSee('data-photo-modal-image', false)
            ->assertDontSee('target="_blank"', false);
    }

    private function deliveryFor(User $creator): Delivery
    {
        $school = School::query()->create([
            'school_code' => 'SC-100',
            'emis_code' => 'EMIS-100',
            'name' => 'বিদ্যালয়',
        ]);

        return Delivery::query()->create([
            'school_id' => $school->id,
            'date' => '2026-09-25',
            'bun_quantity' => 4,
            'egg_quantity' => 2,
            'banana_quantity' => 1,
            'chalan_disk' => 'local',
            'chalan_path' => 'test/chalan.jpg',
            'created_by_user_id' => $creator->id,
        ]);
    }
}
