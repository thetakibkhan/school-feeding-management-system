<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolStudentCount;

class StudentCountResolver
{
    public function forDate(School $school, string $date): ?int
    {
        return $this->forSchoolsOnDate([$school->id], $date)[$school->id] ?? null;
    }

    /** @param list<int> $schoolIds
     * @return array<int, int>
     */
    public function forSchoolsOnDate(array $schoolIds, string $date): array
    {
        if ($schoolIds === []) {
            return [];
        }

        return SchoolStudentCount::query()
            ->whereIn('school_id', $schoolIds)
            ->whereDate('effective_start_date', '<=', $date)
            ->orderBy('school_id')
            ->orderByDesc('effective_start_date')
            ->get(['school_id', 'student_count'])
            ->unique('school_id')
            ->mapWithKeys(fn (SchoolStudentCount $count): array => [(int) $count->school_id => (int) $count->student_count])
            ->all();
    }
}
