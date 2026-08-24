<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class DoctorResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'code' => $this->code,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'phone_number' => $this->phone_number,
                'clinic_name' => $this->clinic_name,
                'specialty' => $this->specialty?->value,
                'status' => $this->status?->value,
                'attachment' => $this->attachment,
                'address' => AddressResource::make($this->whenLoaded('defaultAddress')),
                'is_favorite' => $this->is_favorite,
                'description' => $this->description,
                'meta' => $this->meta,
                'last_visit_at' => $this->last_visit_at?->toISOString(),
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
                'deleted_at' => $this->deleted_at?->toISOString(),
            ];
        }
    }
