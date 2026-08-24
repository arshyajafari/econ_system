<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class OrderItemResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'product' => $this->whenLoaded('product', fn() => [
                    'id' => $this->product->public_id,
                    'code' => $this->product->code,
                    'title' => $this->product->title,
                ]),
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
