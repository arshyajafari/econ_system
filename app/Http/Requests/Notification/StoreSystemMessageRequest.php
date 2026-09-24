<?php

namespace App\Http\Requests\Notification;

use App\Enums\EmployeeActivityType;
use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSystemMessageRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->hasAnyRole([
            Role::ADMIN->value,
            Role::ACCOUNTANT->value,
        ]) ?? false;
    }

    public function rules(): array {
        return [
            'target_type' => ['required', Rule::in(['all', 'users', 'positions'])],
            'user_ids' => ['nullable', 'array', 'required_if:target_type,users', 'min:1'],
            'user_ids.*' => ['string', 'exists:users,public_id'],
            'position_types' => ['nullable', 'array', 'required_if:target_type,positions', 'min:1'],
            'position_types.*' => ['string', Rule::enum(EmployeeActivityType::class)],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
        ];
    }
}
