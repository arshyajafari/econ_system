<?php

    namespace App\Http\Requests;

    use Illuminate\Foundation\Http\FormRequest;

    abstract class BaseFormRequest extends FormRequest {
        public function authorize(): bool {
            return true;
        }

        public function messages(): array {
            return [];
        }

        public function attributes(): array {
            return [];
        }

        public function filters(): array {
            return collect($this->validated())->map(function ($value) {
                return is_string($value) ? trim($value) : $value;
            })->reject(function ($value) {
                return $value === '' || $value === null;
            })->toArray();
        }
    }
