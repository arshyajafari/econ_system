<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class SampleResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'visit' => $this->whenLoaded('visit', fn() => [
                    'id' => $this->visit->public_id,
                    'visit_date' => $this->visit->visit_date?->toISOString(),
                    'status' => $this->visit->status?->value,
                ]),
                'doctor' => $this->when($this->relationLoaded('visit') && $this->visit && $this->visit->relationLoaded('doctor') && $this->visit->doctor,
                    fn() => [
                        'id' => $this->visit->doctor->public_id,
                        'name' => trim($this->visit->doctor->first_name . ' ' . $this->visit->doctor->last_name),
                    ]),
                'employee' => $this->when($this->relationLoaded('visit') && $this->visit && $this->visit->relationLoaded('employee') && $this->visit->employee,
                    fn() => [
                        'id' => $this->visit->employee->public_id,
                        'name' => trim($this->visit->employee->first_name . ' ' . $this->visit->employee->last_name),
                    ]),
                'product' => $this->whenLoaded('product', fn() => [
                    'id' => $this->product->public_id,
                    'code' => $this->product->code,
                    'title' => $this->product->title,
                ]),
                'quantity' => $this->quantity,
                'description' => $this->description,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ];
        }
    }
