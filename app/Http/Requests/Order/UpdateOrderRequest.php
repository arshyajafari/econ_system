<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\CrudRequest;
use App\Models\Order;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends CrudRequest {
    public function rules(): array {
        return [
            'customer_id' => ['sometimes', 'string', 'exists:customers,public_id'],
            'sales_employee_id' => ['sometimes', 'string', 'exists:employees,public_id'],
            'description' => ['sometimes', 'nullable', 'string'],
            'ordered_at' => ['sometimes', 'nullable', 'date'],
            'discount_type' => ['sometimes', 'nullable', Rule::in(Order::DISCOUNT_TYPES)],
            'discount_value' => ['sometimes', 'nullable', 'numeric', 'gte:0'],
            'offer_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'offer_description' => ['sometimes', 'nullable', 'string'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'string', 'exists:products,public_id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
        ];
    }
}
