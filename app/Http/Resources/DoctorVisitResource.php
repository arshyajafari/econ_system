<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class DoctorVisitResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'doctor' => $this->whenLoaded('doctor', fn() => [
                    'id' => $this->doctor->public_id,
                    'code' => $this->doctor->code,
                    'name' => trim($this->doctor->first_name . ' ' . $this->doctor->last_name),
                    'specialty' => $this->doctor->specialty?->value ?? $this->doctor->specialty,
                ]),
                'employee' => $this->whenLoaded('employee', fn() => [
                    'id' => $this->employee->public_id,
                    'name' => trim($this->employee->first_name . ' ' . $this->employee->last_name),
                ]),
                'visit_date' => $this->visit_date?->toDateString(),
                'description' => $this->description,
                'samples' => DoctorVisitSampleResource::collection($this->whenLoaded('samples')),
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ];
        }
    }
