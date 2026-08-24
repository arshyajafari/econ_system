<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class CustomerLedgerTransactionResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'type' => $this->type?->value,
                'debit' => $this->debit,
                'credit' => $this->credit,
                'balance' => $this->balance,
                'amount' => $this->amount,
                'transaction_at' => $this->transaction_at?->toISOString(),
                'description' => $this->description,
                'source' => $this->sourceData(),
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
