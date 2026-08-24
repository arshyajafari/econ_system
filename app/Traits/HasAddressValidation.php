<?php

    namespace App\Traits;

    trait HasAddressValidation {
        protected function addressRules(): array {
            return [
                'address' => [
                    'nullable',
                    'array',
                ],
                'address.address' => [
                    'required_with:address',
                    'string',
                    'max:500',
                ],
                'address.city' => [
                    'required_with:address',
                    'string',
                    'max:100',
                ],
                'address.province' => [
                    'required_with:address',
                    'string',
                    'max:100',
                ],
                'address.postal_code' => [
                    'nullable',
                    'string',
                    'max:20',
                ],
                'address.latitude' => [
                    'nullable',
                    'numeric',
                ],
                'address.longitude' => [
                    'nullable',
                    'numeric',
                ],
                'address.is_default' => [
                    'sometimes',
                    'boolean',
                ],
            ];
        }
    }
