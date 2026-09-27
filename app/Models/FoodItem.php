<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['key', 'name', 'unit', 'unit_weight_grams', 'unit_price'])]
class FoodItem extends Model
{
    /**
     * @return BelongsToMany<FoodSchedule, $this>
     */
    public function schedules(): BelongsToMany
    {
        return $this->belongsToMany(FoodSchedule::class, 'food_schedule_items')->withTimestamps();
    }
}
