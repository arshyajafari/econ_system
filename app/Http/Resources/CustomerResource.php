<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class CustomerResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'id' => $this->public_id,
                'code' => $this->code,
                'customer_name' => $this->customer_name,
                'type' => $this->type,
                'owner_name' => $this->owner_name,
                'manager_name' => $this->manager_name,
                'economic_code' => $this->economic_code,
                'national_code' => $this->national_code,
                'phone_number' => $this->phone_number,
                'telephone_number' => $this->telephone_number,
                'social_address' => $this->social_address,
                'birth_date' => $this->birth_date?->toISOString(),
                'status' => $this->status,
                'description' => $this->description,
                'meta' => $this->meta,
                'address' => AddressResource::make($this->whenLoaded('defaultAddress')),
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
                'deleted_at' => $this->deleted_at?->toISOString(),
            ];
        }
    }
