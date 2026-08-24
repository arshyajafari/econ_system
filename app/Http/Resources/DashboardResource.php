<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class DashboardResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'sales' => [
                    'today' => $this->sales['today'],
                    'month' => $this->sales['month'],
                    'year' => $this->sales['year'],
                ],
                'orders' => [
                    'today' => $this->orders['today'],
                    'month' => $this->orders['month'],
                ],
                'payments' => [
                    'today' => $this->payments['today'],
                    'month' => $this->payments['month'],
                ],
                'receivables' => [
                    'total' => $this->receivables['total'],
                ],
                'returns' => [
                    'pending' => $this->returns['pending'],
                    'confirmed' => $this->returns['confirmed'],
                ],
                'deliveries' => [
                    'pending' => $this->deliveries['pending'],
                    'shipped' => $this->deliveries['shipped'],
                ],
                'visits' => [
                    'today' => $this->visits['today'],
                    'month' => $this->visits['month'],
                ],
                'samples' => [
                    'today' => $this->samples['today'],
                    'month' => $this->samples['month'],
                ],
                'recent' => [
                    'orders' => $this->recent['orders'],
                    'payments' => $this->recent['payments'],
                    'returns' => $this->recent['returns'],
                    'visits' => $this->recent['visits'],
                ],
            ];
        }
    }
