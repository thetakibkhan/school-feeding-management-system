<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolStudentCount;

class StudentCountResolver
{
    public function forDate(School $school, string $date): ?int
    {
        return SchoolStudentCount::query()
            ->where('school_id', $school->id)
            ->whereDate('effective_start_date', '<=', $date)
            ->orderByDesc('effective_start_date')
            ->value('student_count');
    }
}
