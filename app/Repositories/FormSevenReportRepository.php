<?php

namespace App\Repositories;

use App\Models\Delivery;
use App\Models\School;
use Illuminate\Database\Eloquent\Collection;

class FormSevenReportRepository
{
    /** @return Collection<int, School> */
    public function schools(): Collection
    {
        return School::query()
            ->orderBy('school_code')
            ->get(['id', 'school_code', 'emis_code', 'name']);
    }

    /** @return Collection<int, Delivery> */
    public function deliveriesForMonth(string $firstDate, string $lastDate): Collection
    {
        return Delivery::query()
            ->whereBetween('date', [$firstDate, $lastDate])
            ->get(['school_id', 'bun_quantity', 'egg_quantity', 'banana_quantity']);
    }
}
