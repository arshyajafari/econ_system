<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sales' => [
                'today' => data_get($this->resource, 'sales.today', 0),
                'month' => data_get($this->resource, 'sales.month', 0),
                'year' => data_get($this->resource, 'sales.year', 0),
            ],
            'orders' => [
                'today' => data_get($this->resource, 'orders.today', 0),
                'month' => data_get($this->resource, 'orders.month', 0),
            ],
            'payments' => [
                'today' => data_get($this->resource, 'payments.today', 0),
                'month' => data_get($this->resource, 'payments.month', 0),
            ],
            'receivables' => [
                'total' => data_get($this->resource, 'receivables.total', 0),
            ],
            'returns' => [
                'pending' => data_get($this->resource, 'returns.pending', 0),
                'confirmed' => data_get($this->resource, 'returns.confirmed', 0),
            ],
            'deliveries' => [
                'pending' => data_get($this->resource, 'deliveries.pending', 0),
                'shipped' => data_get($this->resource, 'deliveries.shipped', 0),
            ],
            'visits' => [
                'today' => data_get($this->resource, 'visits.today', 0),
                'month' => data_get($this->resource, 'visits.month', 0),
            ],
            'samples' => [
                'today' => data_get($this->resource, 'samples.today', 0),
                'month' => data_get($this->resource, 'samples.month', 0),
            ],
            'recent' => [
                'orders' => data_get($this->resource, 'recent.orders', []),
                'payments' => data_get($this->resource, 'recent.payments', []),
                'returns' => data_get($this->resource, 'recent.returns', []),
                'visits' => data_get($this->resource, 'recent.visits', []),
            ],
        ];
    }
}
