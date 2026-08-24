<?php

    namespace App\Http\Requests\Product;

    use App\Http\Requests\CrudRequest;

    class ChangeProductStatusRequest extends CrudRequest {
        public function rules(): array {
            return [
                'status' => [
                    'required',
                    'string',
                ],
            ];
        }
    }
