<?php

namespace Tests\Feature;

use App\Models\School;
use Database\Seeders\AnwaraSchoolSeeder;
use Database\Seeders\SchoolPrincipalContactSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolPrincipalContactSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_contacts_are_seeded_for_all_schools_without_changing_their_student_counts(): void
    {
        $this->seed(AnwaraSchoolSeeder::class);
        School::query()->where('school_code', 'AN-001')->firstOrFail()->studentCounts()->firstOrFail()->update([
            'student_count' => 205,
        ]);

        $this->seed(SchoolPrincipalContactSeeder::class);

        $this->assertSame(110, School::query()->whereNotNull('principal_name')->whereNotNull('principal_mobile')->count());
        $this->assertDatabaseHas('schools', [
            'school_code' => 'AN-001',
            'name' => 'বৈরাগ সপ্রাবি',
            'principal_name' => 'মো: গোলাম জিলানী',
            'principal_mobile' => '০১৮৪০৬৬৬০৫৫',
        ]);
        $this->assertDatabaseHas('schools', [
            'school_code' => 'AN-009',
            'name' => 'উত্তর গুয়াপঞ্চক সপ্রাবি',
            'principal_name' => 'দীপিকা দাশ',
        ]);
        $this->assertDatabaseHas('schools', [
            'school_code' => 'AN-110',
            'name' => 'মাহাতা পাটনীকোঠা',
            'principal_name' => 'সহজেসমিন আকতার',
            'principal_mobile' => '১৭৩৪৪০৮৪৭৮',
        ]);
        $this->assertDatabaseHas('school_student_counts', [
            'student_count' => 205,
            'effective_start_date' => '2026-09-01 00:00:00',
        ]);
    }
}
