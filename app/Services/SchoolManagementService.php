<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolStudentCount;
use App\Repositories\SchoolRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SchoolManagementService
{
    public function __construct(private readonly SchoolRepository $schools) {}

    /**
     * @return Collection<int, School>
     */
    public function listSchools(?string $search): Collection
    {
        return $this->schools->search($search);
    }

    public function createSchool(string $schoolCode, string $emisCode, string $name, int $studentCount, string $effectiveStartDate): School
    {
        return DB::transaction(function () use ($schoolCode, $emisCode, $name, $studentCount, $effectiveStartDate): School {
            $school = $this->schools->create([
                'school_code' => $schoolCode,
                'emis_code' => $emisCode,
                'name' => $name,
            ]);

            $this->schools->addStudentCount($school, $studentCount, $effectiveStartDate);

            return $school;
        });
    }

    public function updateSchool(School $school, string $schoolCode, string $emisCode, string $name): School
    {
        $school->fill([
            'school_code' => $schoolCode,
            'emis_code' => $emisCode,
            'name' => $name,
        ]);

        return $this->schools->save($school);
    }

    public function addStudentCount(School $school, int $studentCount, string $effectiveStartDate): SchoolStudentCount
    {
        return $this->schools->addStudentCount($school, $studentCount, $effectiveStartDate);
    }

    /**
     * @throws SchoolDeletionBlocked
     */
    public function deleteSchool(School $school): void
    {
        if ($this->schools->hasDeliveryRecords($school)) {
            throw new SchoolDeletionBlocked('This school cannot be deleted because historical delivery and report data references it.');
        }

        DB::transaction(fn () => $this->schools->delete($school));
    }
}
