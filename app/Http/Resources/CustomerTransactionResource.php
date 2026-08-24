<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class CustomerTransactionResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'customer' => $this->whenLoaded('customer', fn() => [
                    'id' => $this->customer->public_id,
                    'name' => $this->customer->customer_name,
                ]),
                'type' => $this->type?->value,
                'amount' => $this->amount,
                'transaction_at' => $this->transaction_at?->toISOString(),
                'description' => $this->description,
                'source' => $this->sourceData(),
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ];
        }

        protected function sourceData(): ?array {
            if ($this->relationLoaded('invoice') && $this->invoice) {
                return [
                    'type' => 'invoice',
                    'id' => $this->invoice->public_id,
                    'code' => $this->invoice->code,
                ];
            }

            if ($this->relationLoaded('payment') && $this->payment) {
                return [
                    'type' => 'payment',
                    'id' => $this->payment->public_id,
                    'reference_number' => $this->payment->reference_number,
                ];
            }

            if ($this->relationLoaded('orderReturn') && $this->orderReturn) {
                return [
                    'type' => 'order_return',
                    'id' => $this->orderReturn->public_id,
                    'code' => $this->orderReturn->code,
                ];
            }

            return null;
        }
    }
