<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'title' => $this->title,
            'amount' => $this->amount,
            'category' => $this->category?->value,
            'expense_date' => $this->expense_date?->toDateString(),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->public_id,
                'name' => $this->employee->fullName,
            ]),
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->public_id,
                'name' => $this->creator->employee?->fullName ?? $this->creator->login,
            ]),
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
