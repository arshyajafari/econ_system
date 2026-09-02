<?php

    namespace App\Http\Resources;

    use App\Http\Resources\AddressResource;
    use App\Http\Resources\UserResource;
    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class EmployeeResource extends JsonResource {
        public function toArray(Request $request): array {

            return [
                'id' => $this->public_id,
                'code' => $this->code,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'national_code' => $this->national_code,
                'phone_number' => $this->phone_number,
                'email' => $this->email,
                'gender' => $this->gender,
                'birth_date' => $this->birth_data?->toISOString(),
                'employment_type' => $this->employment_type,
                'hire_date' => $this->hire_date?->toISOString(),
                'termination_date' => $this->termination_date?->toISOString(),
                'status' => $this->status,
                'description' => $this->description,
                'address' => AddressResource::make($this->whenLoaded('defaultAddress')),
                'user' => UserResource::make($this->whenLoaded('user')),
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
                'deleted_at' => $this->deleted_at?->toISOString(),
            ];
        }
    }
