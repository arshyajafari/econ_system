<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class VisitResource extends JsonResource {
        public function toArray(Request $request): array {
            $doctor = $this->relationLoaded('doctor') ? $this->doctor : null;

            return [
                'id' => $this->public_id,
                'doctor' => $doctor ? [
                    'id' => $doctor->public_id,
                    'name' => trim($doctor->first_name . ' ' . $doctor->last_name),
                    'specialty' => $doctor->specialty?->value,
                    'clinic_name' => $doctor->clinic_name,
                ] : null,
                'doctor_name' => $doctor ? trim($doctor->first_name . ' ' . $doctor->last_name) : null,
                'employee' => $this->whenLoaded('employee', fn() => [
                    'id' => $this->employee->public_id,
                    'name' => trim($this->employee->first_name . ' ' . $this->employee->last_name),
                ]),
                'status' => $this->status?->value,
                'visit_date' => $this->visit_date?->toISOString(),
                'purpose' => $this->purpose,
                'description' => $this->description,
                'samples' => SampleResource::collection($this->whenLoaded('samples')),
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ];
        }
    }
