<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class DeliveryResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'order' => $this->whenLoaded('order', fn() => [
                    'id' => $this->order->public_id,
                    'code' => $this->order->code,
                    'status' => $this->order->status?->value,
                ]),
                'customer' => $this->whenLoaded('customer', fn() => [
                    'id' => $this->customer->public_id,
                    'code' => $this->customer->code,
                    'name' => $this->customer->customer_name,
                ]),
                'employee' => $this->whenLoaded('employee', fn() => [
                    'id' => $this->employee->public_id,
                    'name' => trim($this->employee->first_name . ' ' . $this->employee->last_name),
                ]),
                'status' => $this->status?->value,
                'prepared_at' => $this->prepared_at?->toISOString(),
                'shipped_at' => $this->shipped_at?->toISOString(),
                'delivered_at' => $this->delivered_at?->toISOString(),
                'cancelled_at' => $this->cancelled_at?->toISOString(),
                'recipient_name' => $this->recipient_name,
                'recipient_phone' => $this->recipient_phone,
                'address' => $this->address,
                'description' => $this->description,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ];
        }
    }
