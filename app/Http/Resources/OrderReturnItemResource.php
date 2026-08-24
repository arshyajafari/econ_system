<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class OrderReturnItemResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'order_item_id' => $this->whenLoaded('orderItem', fn() => $this->orderItem->public_id),
                'product' => $this->whenLoaded('product', fn() => [
                    'id' => $this->product->public_id,
                    'title' => $this->product->title,
                    'code' => $this->product->code,
                ]),
                'quantity' => $this->quantity,
                'unit_price' => $this->unit_price,
                'total_price' => $this->total_price,
                'description' => $this->description,
                'allocations' => OrderReturnItemAllocationResource::collection($this->whenLoaded('allocations')),
            ];
        }
    }
