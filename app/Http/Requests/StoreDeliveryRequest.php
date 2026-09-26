<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'bun_quantity' => ['required', 'integer', 'min:0'],
            'egg_quantity' => ['required', 'integer', 'min:0'],
            'banana_quantity' => ['required', 'integer', 'min:0'],
            'chalan_number' => ['required', 'string', 'max:100'],
            'chalan_date' => ['required', 'date_format:Y-m-d'],
            'chalan_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $total = $this->integer('bun_quantity')
                + $this->integer('egg_quantity')
                + $this->integer('banana_quantity');

            if ($total === 0) {
                $validator->errors()->add('bun_quantity', 'Enter a positive quantity for at least one item. Leave the entry empty if nothing was delivered.');
            }
        }];
    }
}
