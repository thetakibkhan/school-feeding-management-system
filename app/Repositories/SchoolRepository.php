<?php

namespace App\Repositories;

use App\Models\School;
use App\Models\SchoolStudentCount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchoolRepository
{
    /**
     * @return Collection<int, School>
     */
    public function search(?string $search): Collection
    {
        return School::query()
            ->with(['studentCounts' => fn ($query) => $query->orderByDesc('effective_start_date')])
            ->when($search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $term = '%'.$search.'%';

                    $query->where('name', 'like', $term)
                        ->orWhere('school_code', 'like', $term)
                        ->orWhere('emis_code', 'like', $term);
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function create(array $attributes): School
    {
        return School::query()->create($attributes);
    }

    public function save(School $school): School
    {
        $school->save();

        return $school->refresh();
    }

    public function addStudentCount(School $school, int $studentCount, string $effectiveStartDate): SchoolStudentCount
    {
        return $school->studentCounts()->create([
            'student_count' => $studentCount,
            'effective_start_date' => $effectiveStartDate,
        ]);
    }

    public function hasDeliveryRecords(School $school): bool
    {
        return Schema::hasTable('deliveries')
            && DB::table('deliveries')->where('school_id', $school->id)->exists();
    }

    public function delete(School $school): void
    {
        $school->delete();
    }
}
