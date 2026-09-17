<?php

namespace App\Http\Resources;

use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource {
    public function toArray(Request $request): array {
        $confirmedPaidAmount = $this->relationLoaded('payments')
            ? (float) $this->payments->where('status', PaymentStatus::CONFIRMED)->sum('amount')
            : null;

        return [
            'id' => $this->public_id,
            'code' => $this->code,
            'order' => $this->whenLoaded('order', fn() => [
                'id' => $this->order->public_id,
                'code' => $this->order->code,
                'status' => $this->order->status?->value,
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
            'is_settled' => $confirmedPaidAmount !== null
                ? $confirmedPaidAmount >= (float) $this->total_amount
                : null,
            'paid_amount' => $confirmedPaidAmount,
            'issued_at' => $this->issued_at?->toISOString(),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'description' => $this->description,
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
