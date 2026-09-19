<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\CrudRequest;
use App\Models\OrderItem;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends CrudRequest {
    public function rules(): array {
        return [
            'customer_id' => ['required','string','exists:customers,public_id'],
            'ordered_at' => ['nullable','date'],
            'description' => ['nullable','string'],
            'discount_type' => ['nullable',Rule::in(\App\Models\Order::DISCOUNT_TYPES)],
            'discount_value' => ['nullable','numeric','gte:0'],
            'offer_title' => ['nullable','string','max:255'],
            'offer_description' => ['nullable','string'],
            'items' => ['required','array','min:1'],
            'items.*.product_id' => ['required','string','exists:products,public_id'],
            'items.*.quantity' => ['required','integer','min:1'],
            'items.*.unit_price' => ['required','numeric','min:0'],
            'items.*.description' => ['nullable','string'],
            'items.*.discount_type' => ['nullable',Rule::in(OrderItem::DISCOUNT_TYPES)],
            'items.*.discount_value' => ['nullable','numeric','gte:0'],
            'items.*.offer_type' => ['nullable',Rule::in(OrderItem::OFFER_TYPES)],
            'items.*.offer_buy_quantity' => ['nullable','integer','gte:0'],
            'items.*.offer_free_quantity' => ['nullable','integer','gte:0'],
            'items.*.offer_title' => ['nullable','string','max:255'],
        ];
    }
}
