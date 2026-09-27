<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DailyDeliveryReportRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['date' => today()->toDateString()]);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function selectedDate(): string
    {
        return $this->validated('date');
    }
}
