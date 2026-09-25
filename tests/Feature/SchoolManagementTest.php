<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\SchoolStudentCount;
use App\Models\User;
use App\Services\StudentCountResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class SchoolManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_school_with_its_initial_effective_student_count(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post('/admin/schools', [
            'school_code' => '00123',
            'emis_code' => '000456',
            'name' => 'আনোয়ারা সরকারি প্রাথমিক বিদ্যালয়',
            'student_count' => 100,
            'effective_start_date' => '2026-09-01',
        ]);

        $response->assertRedirect('/admin/schools');
        $this->assertDatabaseHas('schools', [
            'school_code' => '00123',
            'emis_code' => '000456',
            'name' => 'আনোয়ারা সরকারি প্রাথমিক বিদ্যালয়',
        ]);
        $this->assertDatabaseHas('school_student_counts', [
            'student_count' => 100,
            'effective_start_date' => '2026-09-01 00:00:00',
        ]);
    }

    public function test_school_creation_requires_a_utf8_name_unique_codes_and_a_positive_student_count(): void
    {
        School::query()->create([
            'school_code' => '00123',
            'emis_code' => '000456',
            'name' => 'বিদ্যমান বিদ্যালয়',
        ]);

        $response = $this->actingAs($this->admin())->from('/admin/schools')->post('/admin/schools', [
            'school_code' => '00123',
            'emis_code' => '000456',
            'name' => '',
            'student_count' => 0,
            'effective_start_date' => '',
        ]);

        $response->assertRedirect('/admin/schools');
        $response->assertSessionHasErrors(['school_code', 'emis_code', 'name', 'student_count', 'effective_start_date']);
    }

    public function test_admin_can_add_effective_dated_counts_and_resolve_the_applicable_count(): void
    {
        $school = $this->school();
        SchoolStudentCount::query()->create([
            'school_id' => $school->id,
            'student_count' => 100,
            'effective_start_date' => '2026-09-01',
        ]);

        $this->actingAs($this->admin())->post('/admin/schools/'.$school->id.'/student-counts', [
            'student_count' => 120,
            'effective_start_date' => '2026-09-15',
        ])->assertRedirect('/admin/schools/'.$school->id);

        $resolver = app(StudentCountResolver::class);

        $this->assertSame(100, $resolver->forDate($school, '2026-09-10'));
        $this->assertSame(120, $resolver->forDate($school, '2026-09-15'));
        $this->assertSame(120, $resolver->forDate($school, '2026-09-25'));
        $this->assertNull($resolver->forDate($school, '2026-08-31'));

        $this->actingAs($this->admin())
            ->get('/admin/schools/'.$school->id)
            ->assertOk()
            ->assertSee($school->name);
    }

    public function test_admin_can_edit_school_code_emis_code_and_utf8_name(): void
    {
        $school = $this->school();

        $this->actingAs($this->admin())->put('/admin/schools/'.$school->id, [
            'school_code' => 'ANW-UPDATED',
            'emis_code' => 'EMIS-UPDATED',
            'name' => 'আপডেট করা বিদ্যালয়',
        ])->assertRedirect('/admin/schools/'.$school->id);

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'school_code' => 'ANW-UPDATED',
            'emis_code' => 'EMIS-UPDATED',
            'name' => 'আপডেট করা বিদ্যালয়',
        ]);
    }

    public function test_duplicate_effective_start_dates_are_rejected_for_one_school(): void
    {
        $school = $this->school();
        SchoolStudentCount::query()->create([
            'school_id' => $school->id,
            'student_count' => 100,
            'effective_start_date' => '2026-09-01',
        ]);

        $response = $this->actingAs($this->admin())
            ->from('/admin/schools/'.$school->id)
            ->post('/admin/schools/'.$school->id.'/student-counts', [
                'student_count' => 120,
                'effective_start_date' => '2026-09-01',
            ]);

        $response->assertRedirect('/admin/schools/'.$school->id);
        $response->assertSessionHasErrors('effective_start_date');
    }

    public function test_admin_can_search_by_partial_school_name_school_code_or_emis_code(): void
    {
        $matchingSchool = $this->school([
            'school_code' => 'ANW-001',
            'emis_code' => 'EMIS-12345',
            'name' => 'আনোয়ারা মডেল সরকারি প্রাথমিক বিদ্যালয়',
        ]);
        $this->school([
            'school_code' => 'ANW-002',
            'emis_code' => 'EMIS-67890',
            'name' => 'বাঁশখালী সরকারি প্রাথমিক বিদ্যালয়',
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/schools?search=123')
            ->assertOk()
            ->assertSee($matchingSchool->name)
            ->assertDontSee('বাঁশখালী সরকারি প্রাথমিক বিদ্যালয়');
    }

    public function test_unused_school_deletion_removes_its_student_count_history(): void
    {
        $school = $this->school();
        SchoolStudentCount::query()->create([
            'school_id' => $school->id,
            'student_count' => 100,
            'effective_start_date' => '2026-09-01',
        ]);

        $this->actingAs($this->admin())->delete('/admin/schools/'.$school->id)
            ->assertRedirect('/admin/schools');

        $this->assertDatabaseMissing('schools', ['id' => $school->id]);
        $this->assertDatabaseMissing('school_student_counts', ['school_id' => $school->id]);
    }

    public function test_school_deletion_is_blocked_when_delivery_history_references_the_school(): void
    {
        $school = $this->school();
        Schema::create('deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });
        DB::table('deliveries')->insert([
            'school_id' => $school->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin())->from('/admin/schools')->delete('/admin/schools/'.$school->id);

        $response->assertRedirect('/admin/schools');
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('schools', ['id' => $school->id]);

        Schema::dropIfExists('deliveries');
    }

    public function test_field_staff_cannot_manage_schools(): void
    {
        $fieldStaff = User::factory()->create(['role' => UserRole::FieldStaff]);

        $this->actingAs($fieldStaff)->get('/admin/schools')->assertForbidden();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    /**
     * @param array{school_code?: string, emis_code?: string, name?: string} $attributes
     */
    private function school(array $attributes = []): School
    {
        return School::query()->create([
            'school_code' => $attributes['school_code'] ?? 'SC-'.fake()->unique()->numerify('###'),
            'emis_code' => $attributes['emis_code'] ?? 'EMIS-'.fake()->unique()->numerify('#####'),
            'name' => $attributes['name'] ?? 'পরীক্ষা বিদ্যালয়',
        ]);
    }
}
