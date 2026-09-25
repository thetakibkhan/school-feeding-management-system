<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['date'])]
class FoodSchedule extends Model
{
    /**
     * @return BelongsToMany<FoodItem, $this>
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(FoodItem::class, 'food_schedule_items')->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
