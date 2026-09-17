<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SampleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing(['visit.doctor', 'visit.employee', 'product']);

        $visit = $this->visit;
        $doctor = $visit?->doctor;
        $employee = $visit?->employee;
        $product = $this->product;

        return [
            'id' => $this->public_id,
            'visit' => $visit ? [
                'id' => $visit->public_id,
                'visit_date' => $visit->visit_date?->toISOString(),
                'status' => $visit->status?->value,
            ] : null,
            'doctor' => $doctor ? [
                'id' => $doctor->public_id,
                'name' => $doctor->full_name ?: trim($doctor->first_name . ' ' . $doctor->last_name),
            ] : null,
            'doctor_name' => $doctor?->full_name ?: ($doctor ? trim($doctor->first_name . ' ' . $doctor->last_name) : null),
            'employee' => $employee ? [
                'id' => $employee->public_id,
                'name' => trim($employee->first_name . ' ' . $employee->last_name),
            ] : null,
            'product' => $product ? [
                'id' => $product->public_id,
                'code' => $product->code,
                'title' => $product->title,
            ] : null,
            'product_name' => $product?->title,
            'quantity' => $this->quantity,
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
