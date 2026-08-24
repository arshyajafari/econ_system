<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class CustomerLedgerResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'customer' => [
                    'id' => $this->customer->public_id,
                    'name' => $this->customer->customer_name,
                ],
                'opening_balance' => $this->opening_balance,
                'total_debit' => $this->total_debit,
                'total_credit' => $this->total_credit,
                'closing_balance' => $this->closing_balance,
                'transactions' => CustomerLedgerTransactionResource::collection($this->transactions),
            ];
        }
    }
