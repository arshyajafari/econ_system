<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeLocationRequest extends FormRequest {
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'source' => ['nullable', 'string', 'max:30'],
            'captured_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
            'client_operation_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
