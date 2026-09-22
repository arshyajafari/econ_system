<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryOrderSourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'code' => $this->code,
            'status' => $this->status?->value,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->public_id,
                'code' => $this->customer->code,
                'name' => $this->customer->customer_name,
                'phone' => $this->customer->phone_number,
            ]),
        ];
    }
}
