<?php

    declare(strict_types=1);

    namespace App\Http\Requests\Auth;

    use Illuminate\Foundation\Http\FormRequest;

    class LoginRequest extends FormRequest {
        public function authorize(): bool {
            return true;
        }

        public function rules(): array {
            return [
                'login' => [
                    'required',
                    'string',
                    'max:50',
                ],
                'password' => [
                    'required',
                    'string',
                    'min:6',
                ],
                'device_id' => [
                    'required',
                    'uuid',
                ],
                'platform' => [
                    'required',
                    'string',
                    'max:20',
                ],
                'app_version' => [
                    'nullable',
                    'string',
                    'max:20',
                ],
                'platform_version' => [
                    'nullable',
                    'string',
                    'max:50',
                ],
                'push_token' => [
                    'nullable',
                    'string',
                ],
            ];
        }
    }
