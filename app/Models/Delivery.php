<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'school_id',
    'date',
    'bun_quantity',
    'egg_quantity',
    'banana_quantity',
    'chalan_number',
    'chalan_date',
    'chalan_disk',
    'chalan_path',
    'created_by_user_id',
    'updated_by_user_id',
])]
class Delivery extends Model
{
    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /** @return HasMany<DeliveryCorrectionHistory, $this> */
    public function correctionHistory(): HasMany
    {
        return $this->hasMany(DeliveryCorrectionHistory::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'chalan_date' => 'date',
            'bun_quantity' => 'integer',
            'egg_quantity' => 'integer',
            'banana_quantity' => 'integer',
        ];
    }
}
