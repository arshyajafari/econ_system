<?php

    namespace App\Http\Requests\Brand;

    use App\Http\Requests\CrudRequest;

    class ChangeBrandActivityRequest extends CrudRequest {
        public function rules(): array {
            return [
                'is_active' => [
                    'required',
                    'boolean',
                ],
            ];
        }
    }
