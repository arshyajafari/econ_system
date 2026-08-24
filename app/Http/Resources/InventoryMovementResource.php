<?php

    namespace App\Http\Resources\InventoryMovement;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class InventoryMovementResource extends JsonResource {
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
                'type' => $this->type,
                'quantity' => $this->quantity,
                'reason' => $this->reason,
                'description' => $this->description,
                'moved_at' => $this->moved_at?->toISOString(),
                'created_at' => $this->created_at?->toISOString(),
            ];
        }
    }
