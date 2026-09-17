<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerLedgerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'customer' => [
                'id' => $this->resource['customer']->public_id,
                'name' => $this->resource['customer']->customer_name,
            ],
            'opening_balance' => $this->resource['opening_balance'],
            'total_debit' => $this->resource['total_debit'],
            'total_credit' => $this->resource['total_credit'],
            'closing_balance' => $this->resource['closing_balance'],
            'transactions' => CustomerLedgerTransactionResource::collection($this->resource['transactions']),
        ];
    }
}
