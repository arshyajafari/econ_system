<?php

namespace App\Http\Resources;

use App\Models\ScientificVisitorInventory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScientificVisitorInventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing(ScientificVisitorInventory::DEFAULT_RELATIONS);

        $product = $this->product;
        $employee = $this->employee;
        $price = $product?->currentPrice;

        return [
            'id' => $this->public_id,
            'employee' => $employee ? [
                'id' => $employee->public_id,
                'code' => $employee->code,
                'name' => $employee->full_name,
            ] : null,
            'product' => $product ? [
                'id' => $product->public_id,
                'code' => $product->code,
                'title' => $product->title,
                'sale_price' => $price?->sale_price,
            ] : null,
            'received_quantity' => $this->received_quantity,
            'used_quantity' => $this->used_quantity,
            'available_quantity' => $this->available_quantity,
            'last_received_at' => $this->last_received_at?->toISOString(),
            'description' => $this->description,
        ];
    }
}