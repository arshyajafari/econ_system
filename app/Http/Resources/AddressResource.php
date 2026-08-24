<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class AddressResource extends JsonResource {
        public function toArray(Request $request): array {
            return [
                'address' => $this->address,
                'province' => $this->province,
                'city' => $this->city,
                'postal_code' => $this->postal_code,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'is_default' => $this->is_default
            ];
        }
    }
