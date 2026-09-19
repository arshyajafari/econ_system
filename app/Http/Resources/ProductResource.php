<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalQuantity = (int) ($this->inventory_batches_sum_quantity ?? 0);
        $reservedQuantity = (int) ($this->inventory_batches_sum_reserved_quantity ?? 0);

        return [
            'id' => $this->public_id,
            'code' => $this->code,
            'title' => $this->title,
            'current_price' => $this->whenLoaded('currentPrice', fn() => [
                'id' => $this->currentPrice->public_id,
                'sale_price' => $this->currentPrice->sale_price,
                'effective_from' => $this->currentPrice->effective_from?->toISOString(),
                'effective_to' => $this->currentPrice->effective_to?->toISOString(),
            ]),
            'available_quantity' => max(0, $totalQuantity - $reservedQuantity),
            'image' => $this->image && !filter_var($this->image, FILTER_VALIDATE_URL)
                ? Storage::disk('public')->url($this->image)
                : $this->image,
            'barcode' => $this->barcode,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'description' => $this->description,
            'brand' => $this->whenLoaded('brand', fn() => [
                'id' => $this->brand->public_id,
                'title' => $this->brand->title,
            ]),
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->public_id,
                'title' => $this->category->title,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
