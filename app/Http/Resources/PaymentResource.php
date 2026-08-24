<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class PaymentResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'invoice' => $this->whenLoaded('invoice', fn() => [
                    'id' => $this->invoice->public_id,
                    'code' => $this->invoice->code,
                    'status' => $this->invoice->status?->value,
                    'total_amount' => $this->invoice->total_amount,
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
                'method' => $this->method?->value,
                'amount' => $this->amount,
                'reference_number' => $this->reference_number,
                'payment_date' => $this->payment_date?->toDateString(),
                'description' => $this->description,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ];
        }
    }
