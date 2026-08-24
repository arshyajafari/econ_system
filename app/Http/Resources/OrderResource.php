<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class OrderResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'code' => $this->code,
                'customer' => $this->whenLoaded('customer', fn() => [
                    'id' => $this->customer->public_id,
                    'code' => $this->customer->code,
                    'customer_name' => $this->customer->customer_name,
                ]),
                'sales_employee' => $this->whenLoaded('salesEmployee', fn() => [
                    'id' => $this->salesEmployee->public_id,
                    'code' => $this->salesEmployee->code,
                    'name' => trim($this->salesEmployee->first_name . ' ' . $this->salesEmployee->last_name),
                ]),
                'status' => $this->status?->value,
                'ordered_at' => $this->ordered_at?->toISOString(),
                'description' => $this->description,
                'meta' => $this->meta,
                'items' => OrderItemResource::collection($this->whenLoaded('items')),
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
                'deleted_at' => $this->deleted_at?->toISOString(),
            ];
        }
    }
