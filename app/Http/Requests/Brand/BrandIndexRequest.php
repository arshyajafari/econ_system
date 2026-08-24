<?php

    namespace App\Http\Requests\Brand;

    use App\Http\Requests\IndexRequest;

    class BrandIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ];
        }
    }
