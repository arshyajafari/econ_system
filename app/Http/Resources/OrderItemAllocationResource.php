<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class OrderItemAllocationResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'inventory_batch' => $this->whenLoaded('inventoryBatch', fn() => [
                    'id' => $this->inventoryBatch->public_id,
                    'batch_number' => $this->inventoryBatch->batch_number,
                    'product' => $this->when($this->inventoryBatch->relationLoaded('product'), fn() => [
                        'id' => $this->inventoryBatch->product->public_id,
                        'code' => $this->inventoryBatch->product->code,
                        'title' => $this->inventoryBatch->product->title,
                    ]),
                ]),
                'quantity' => $this->quantity,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ];
        }
    }
