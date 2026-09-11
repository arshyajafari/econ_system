<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'period' => data_get($this->resource, 'period', []),
            'sales' => data_get($this->resource, 'sales', []),
            'payments' => data_get($this->resource, 'payments', []),
            'orders' => data_get($this->resource, 'orders', []),
            'returns' => data_get($this->resource, 'returns', []),
            'receivables' => data_get($this->resource, 'receivables', []),
            'top_products' => data_get($this->resource, 'top_products', []),
        ];
    }
}
