<?php

    namespace App\Http\Requests\InventoryBatch;

    use App\Http\Requests\BaseFormRequest;
    use App\Validation\ValidationRules;

    class UpdateInventoryBatchRequest extends BaseFormRequest {
        public function rules(): array {
            return [
                'batch_number' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:100',
                ],
                'expire_date' => [
                    'sometimes',
                    'nullable',
                    'date',
                ],
                ...ValidationRules::description(),
            ];
        }
    }
