<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class OrderReturnItemAllocationResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'inventory_batch_id' => $this->whenLoaded('inventoryBatch', fn() => $this->inventoryBatch->public_id),
                'quantity' => $this->quantity,
            ];
        }
    }
