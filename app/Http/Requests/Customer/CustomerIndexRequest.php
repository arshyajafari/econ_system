<?php

    namespace App\Http\Requests\Customer;

    use App\Enums\CustomerStatus;
    use App\Enums\CustomerType;
    use Illuminate\Validation\Rule;
    use App\Http\Requests\IndexRequest;

    class CustomerIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'status' => [
                    'nullable',
                    Rule::enum(CustomerStatus::class),
                ],
                'type' => [
                    'nullable',
                    Rule::enum(CustomerType::class),
                ],
            ];
        }
    }
