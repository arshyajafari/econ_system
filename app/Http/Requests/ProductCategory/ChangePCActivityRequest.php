<?php

    namespace App\Http\Requests\ProductCategory;

    use App\Http\Requests\CrudRequest;

    class ChangePCActivityRequest extends CrudRequest {
        public function rules(): array {
            return [
                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ];
        }
    }
