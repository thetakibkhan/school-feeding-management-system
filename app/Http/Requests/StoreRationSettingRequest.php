<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRationSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'food_item_id' => ['required', 'integer', 'exists:food_items,id'],
            'ration_grams' => ['required', 'integer', 'min:1'],
            'effective_start_date' => [
                'required',
                'date',
                Rule::unique('ration_settings')->where(
                    fn ($query) => $query->where('food_item_id', $this->integer('food_item_id')),
                ),
            ],
        ];
    }
}
