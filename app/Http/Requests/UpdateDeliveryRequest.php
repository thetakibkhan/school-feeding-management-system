<?php

namespace App\Http\Requests;

use App\Models\Delivery;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $delivery = $this->route('delivery');

        return $delivery instanceof Delivery
            && (int) $this->user()?->getAuthIdentifier() === (int) $delivery->created_by_user_id;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'bun_quantity' => ['required', 'integer', 'min:0'],
            'egg_quantity' => ['required', 'integer', 'min:0'],
            'banana_quantity' => ['required', 'integer', 'min:0'],
            'chalan_number' => ['required', 'string', 'max:100'],
            'chalan_date' => ['required', 'date_format:Y-m-d'],
            'chalan_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
