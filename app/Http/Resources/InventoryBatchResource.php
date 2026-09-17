<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryBatchResource extends JsonResource
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
            ] : null,
            'batch_number' => $this->batch_number,
            'expire_date' => $this->expire_date?->toISOString(),
            'received_at' => $this->received_at?->toISOString(),
            'quantity' => $this->quantity,
            'purchase_price' => $this->purchase_price,
            'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => $this->available_quantity,
            'is_expired' => $this->is_expired,
            'is_near_expire' => $this->is_near_expire,
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
