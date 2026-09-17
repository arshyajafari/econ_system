<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->public_id,
            'login' => $this->login,
            'roles' => $this->whenLoaded('roles', fn() => $this->roles->pluck('name')->values()->all(), fn() => $this->getRoleNames()->values()->all()),
            'permissions' => $this->getAllPermissions()->pluck('name')->values()->all(),
            'employee' => [
                'id' => $this->employee->public_id,
                'full_name' => $this->employee->full_name,
                'phone_number' => $this->employee->phone_number,
            ],
        ];
    }
}
