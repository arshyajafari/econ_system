<?php

    namespace App\Http\Requests\Brand;

    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;

    class StoreBrandRequest extends CrudRequest {
        public function rules(): array {
            return [
                'title' => [
                    'required',
                    'string',
                    'max:150',
                ],
                'logo' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
                'sort_order' => [
                    'nullable',
                    'integer',
                    'min:0',
                ],
                'is_active' => [
                    'required',
                    'boolean',
                ],
                ...ValidationRules::description(),
                ...ValidationRules::meta(),
            ];
        }
    }
