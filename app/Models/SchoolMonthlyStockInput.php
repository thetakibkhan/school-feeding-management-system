<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id', 'month', 'boy_count', 'girl_count', 'union_name', 'cluster_name',
    'bun_opening', 'bun_distributed', 'egg_opening', 'egg_distributed',
    'banana_opening', 'banana_distributed', 'biscuit_opening',
    'biscuit_received', 'biscuit_distributed', 'milk_opening',
    'milk_received', 'milk_distributed',
])]
class SchoolMonthlyStockInput extends Model
{
    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
