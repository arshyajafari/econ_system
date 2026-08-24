<?php

    namespace App\Http\Requests\Sample;

    use App\Http\Requests\IndexRequest;

    class SampleIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'visit_id' => [
                    'nullable',
                    'string',
                    'exists:visits,public_id',
                ],
                'product_id' => [
                    'nullable',
                    'string',
                    'exists:products,public_id',
                ],
            ];
        }
    }
