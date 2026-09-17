<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeLocationResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->public_id,
            'employee_id' => $this->employee?->public_id,
            'employee_name' => $this->employee?->full_name,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'accuracy' => $this->accuracy !== null ? (float) $this->accuracy : null,
            'source' => $this->source,
            'captured_at' => $this->captured_at?->toISOString(),
        ];
    }
}
