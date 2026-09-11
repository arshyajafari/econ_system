<?php

    namespace App\Http\Requests\Customer;

    use App\Enums\CustomerStatus;
    use App\Enums\CustomerType;
    use App\Http\Requests\BaseFormRequest;
    use App\Validation\ValidationRules;
    use Illuminate\Validation\Rule;

    class StoreCustomerRequest extends BaseFormRequest {
        public function rules(): array {
            return [
                'customer_name' => [
                    'required',
                    'string',
                    'max:300',
                ],
                'type' => [
                    'required',
                    Rule::enum(CustomerType::class),
                ],
                'owner_name' => [
                    'nullable',
                    'string',
                    'max:120',
                ],
                'manager_name' => [
                    'nullable',
                    'string',
                    'max:120',
                ],
                'phone_number' => [
                    'required',
                    'string',
                    'max:20',
                ],
                'telephone_number' => [
                    'nullable',
                    'string',
                    'max:20',
                ],
                'social_link' => [
                    'nullable',
                    'url',
                    'max:255',
                ],
                'national_code' => [
                    'nullable',
                    'string',
                    'max:25',
                ],
                'economic_code' => [
                    'nullable',
                    'string',
                    'max:25',
                ],
                'status' => [
                    'required',
                    Rule::enum(CustomerStatus::class),
                ],
                ...ValidationRules::address(),
                ...ValidationRules::meta(),
                ...ValidationRules::description()
            ];
        }
    }
