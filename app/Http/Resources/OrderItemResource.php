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
                'current_price' => $price ? [
                    'id' => $price->public_id,
                    'sale_price' => $price->sale_price,
                    'effective_from' => $price->effective_from?->toISOString(),
                ] : null,
                'sale_price' => $price?->sale_price,
            ] : null,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'description' => $this->description,
            'allocations' => OrderItemAllocationResource::collection($this->whenLoaded('allocations')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
