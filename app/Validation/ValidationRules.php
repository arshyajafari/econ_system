<?php

    namespace App\Validation;

    class ValidationRules {
        public static function address(string $prefix = 'address'): array {
            return [
                "{$prefix}" => [
                    'nullable',
                    'array',
                ],
                "{$prefix}.province" => [
                    'required_with:' . $prefix,
                    'string',
                    'max:100',
                ],
                "{$prefix}.city" => [
                    'required_with:' . $prefix,
                    'string',
                    'max:100',
                ],
                "{$prefix}.address" => [
                    'required_with:' . $prefix,
                    'string',
                ],
                "{$prefix}.postal_code" => [
                    'nullable',
                    'string',
                    'max:20',
                ],
                "{$prefix}.latitude" => [
                    'nullable',
                    'numeric',
                    'between:-90,90',
                ],
                "{$prefix}.longitude" => [
                    'nullable',
                    'numeric',
                    'between:-180,180',
                ],
            ];
        }

        public static function description(): array {
            return [
                'description' => [
                    'nullable',
                    'string',
                ],
            ];
        }

        public static function meta(): array {
            return [
                'meta' => [
                    'nullable',
                    'array',
                ],
            ];
        }
    }
