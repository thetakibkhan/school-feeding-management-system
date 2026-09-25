<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_code', 'emis_code', 'name'])]
class School extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolFactory> */
    use HasFactory;

    /**
     * @return HasMany<SchoolStudentCount, $this>
     */
    public function studentCounts(): HasMany
    {
        return $this->hasMany(SchoolStudentCount::class);
    }
}
