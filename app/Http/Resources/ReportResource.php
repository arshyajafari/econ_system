<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'period' => $this->period,
            'sales' => $this->sales,
            'payments' => $this->payments,
            'orders' => $this->orders,
            'returns' => $this->returns,
            'receivables' => $this->receivables,
            'top_products' => $this->top_products,
        ];
    }
}
