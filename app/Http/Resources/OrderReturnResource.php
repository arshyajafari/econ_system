<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class OrderReturnResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'code' => $this->code,
                'order' => $this->whenLoaded('order', fn() => [
                    'id' => $this->order->public_id,
                    'code' => $this->order->code,
                    'status' => $this->order->status?->value,
                ]),
                'customer' => $this->whenLoaded('customer', fn() => [
                    'id' => $this->customer->public_id,
                    'name' => $this->customer->customer_name,
                ]),
                'employee' => $this->whenLoaded('employee', fn() => [
                    'id' => $this->employee->public_id,
                    'name' => trim($this->employee->first_name . ' ' . $this->employee->last_name),
                ]),
                'status' => $this->status?->value,
                'completed_at' => $this->completed_at?->toISOString(),
                'description' => $this->description,
                'items' => OrderReturnItemResource::collection($this->whenLoaded('items')),
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ];
        }
    }
