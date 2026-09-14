<?php

namespace App\Http\Requests\Notification;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSystemMessageRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->hasRole(Role::ADMIN->value) ?? false;
    }

    public function rules(): array {
        return [
            'target_type' => ['required', Rule::in(['all', 'users', 'roles'])],
            'user_ids' => ['nullable', 'array', 'required_if:target_type,users', 'min:1'],
            'user_ids.*' => ['string', 'exists:users,public_id'],
            'role_names' => ['nullable', 'array', 'required_if:target_type,roles', 'min:1'],
            'role_names.*' => ['string', 'max:100'],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
        ];
    }
}
