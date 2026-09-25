<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\NonWorkingDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNonWorkingDateRequest extends FormRequest
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
        $nonWorkingDate = $this->route('nonWorkingDate');

        return [
            'date' => [
                'required',
                'date',
                Rule::unique('non_working_dates', 'date')->ignore($nonWorkingDate instanceof NonWorkingDate ? $nonWorkingDate : null),
            ],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
