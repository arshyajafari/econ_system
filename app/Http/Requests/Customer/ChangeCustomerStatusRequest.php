<?php

    namespace App\Http\Requests\Customer;

    use App\Enums\CustomerStatus;
    use App\Http\Requests\BaseFormRequest;
    use Illuminate\Validation\Rule;

    class ChangeCustomerStatusRequest extends BaseFormRequest {
        public function rules(): array {
            return [
                'status' => [
                    'required',
                    Rule::enum(CustomerStatus::class),
                ],
            ];
        }

        public function status(): CustomerStatus {
            return CustomerStatus::from($this->validated('status'));
        }
    }
