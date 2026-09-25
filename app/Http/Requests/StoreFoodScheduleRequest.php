<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\FoodSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFoodScheduleRequest extends FormRequest
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
        $schedule = $this->route('schedule');

        return [
            'date' => [
                'required',
                'date',
                Rule::unique('food_schedules', 'date')->ignore($schedule instanceof FoodSchedule ? $schedule : null),
            ],
            'food_item_ids' => ['required', 'array', 'min:1'],
            'food_item_ids.*' => ['required', 'integer', 'distinct', 'exists:food_items,id'],
        ];
    }
}
