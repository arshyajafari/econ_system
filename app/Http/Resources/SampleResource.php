<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class SampleResource extends JsonResource {
        public function toArray(Request $request): array {
            $doctor = $this->relationLoaded('visit') && $this->visit?->relationLoaded('doctor') ? $this->visit->doctor : null;
            $product = $this->relationLoaded('product') ? $this->product : null;

            return [
                'id' => $this->public_id,
                'visit' => $this->whenLoaded('visit', fn() => [
                    'id' => $this->visit->public_id,
                    'visit_date' => $this->visit->visit_date?->toISOString(),
                    'status' => $this->visit->status?->value,
                ]),
                'doctor' => $doctor ? [
                    'id' => $doctor->public_id,
                    'name' => trim($doctor->first_name . ' ' . $doctor->last_name),
                ] : null,
                'doctor_name' => $doctor ? trim($doctor->first_name . ' ' . $doctor->last_name) : null,
                'employee' => $this->when($this->relationLoaded('visit') && $this->visit && $this->visit->relationLoaded('employee') && $this->visit->employee,
                    fn() => [
                        'id' => $this->visit->employee->public_id,
                        'name' => trim($this->visit->employee->first_name . ' ' . $this->visit->employee->last_name),
                    ]),
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
