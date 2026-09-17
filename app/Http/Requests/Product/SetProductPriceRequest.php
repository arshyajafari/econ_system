<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class SetProductPriceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'sale_price' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['nullable', 'date', 'before_or_equal:now'],
            'description' => ['nullable', 'string'],
        ];
    }
}
