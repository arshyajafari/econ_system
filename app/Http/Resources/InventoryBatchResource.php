<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class InventoryBatchResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'product' => $this->whenLoaded('product', fn() => [
                    'id' => $this->product->public_id,
                    'code' => $this->product->code,
                    'title' => $this->product->title,
                ]),
                'batch_number' => $this->batch_number,
                'expire_date' => $this->expire_date?->toISOString(),
                'received_at' => $this->received_at?->toISOString(),
                'quantity' => $this->quantity,
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
