<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'month',
    'bun_quantity',
    'egg_quantity',
    'banana_quantity',
    'source_document',
])]
class SchoolMonthlyPlanningQuantity extends Model
{
    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'bun_quantity' => 'integer',
            'egg_quantity' => 'integer',
            'banana_quantity' => 'integer',
        ];
    }
}
