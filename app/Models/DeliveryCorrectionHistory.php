<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'delivery_id',
    'previous_bun_quantity',
    'previous_egg_quantity',
    'previous_banana_quantity',
    'previous_chalan_number',
    'previous_chalan_date',
    'previous_chalan_disk',
    'previous_chalan_path',
    'editor_user_id',
    'edited_at',
])]
class DeliveryCorrectionHistory extends Model
{
    public $timestamps = false;

    /** @return BelongsTo<Delivery, $this> */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
            'previous_chalan_date' => 'date',
        ];
    }
}
