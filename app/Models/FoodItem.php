<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'name', 'unit'])]
class FoodItem extends Model
{
    /**
     * @return HasMany<RationSetting, $this>
     */
    public function rationSettings(): HasMany
    {
        return $this->hasMany(RationSetting::class);
    }

    /**
     * @return BelongsToMany<FoodSchedule, $this>
     */
    public function schedules(): BelongsToMany
    {
        return $this->belongsToMany(FoodSchedule::class, 'food_schedule_items')->withTimestamps();
    }
}
