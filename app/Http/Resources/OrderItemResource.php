<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->relationLoaded('product') ? $this->product : null;
        $price = $product && $product->relationLoaded('currentPrice') ? $product->currentPrice : null;

        return [
            'id' => $this->public_id,
            'product' => $product ? [
                'id' => $product->public_id,
                'code' => $product->code,
                'title' => $product->title,
                'current_price' => $price ? ['id' => $price->public_id, 'sale_price' => $price->sale_price, 'effective_from' => $price->effective_from?->toISOString()] : null,
                'sale_price' => $price?->sale_price,
            ] : null,
            'quantity' => $this->quantity,
            'free_quantity' => $this->offer_free_quantity,
            'fulfillment_quantity' => $this->fulfillmentQuantity(),
            'unit_price' => $this->unit_price,
            'gross_total_price' => round((float) $this->quantity * (float) $this->unit_price, 2),
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'discount_amount' => $this->discount_amount,
            'total_price' => $this->total_price,
            'offer_type' => $this->offer_type,
            'offer_buy_quantity' => $this->offer_buy_quantity,
            'offer_free_quantity' => $this->offer_free_quantity,
            'offer_title' => $this->offer_title,
            'description' => $this->description,
            'allocations' => OrderItemAllocationResource::collection($this->whenLoaded('allocations')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
