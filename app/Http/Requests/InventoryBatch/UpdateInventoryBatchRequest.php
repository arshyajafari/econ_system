<?php

    namespace App\Http\Requests\InventoryBatch;

    use App\Http\Requests\BaseFormRequest;
    use App\Validation\ValidationRules;
    use App\Models\InventoryBatch;

    class UpdateInventoryBatchRequest extends BaseFormRequest {
        public function rules(): array {
            $batch = $this->route('inventoryBatch');

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
                'quantity' => [
                    'sometimes',
                    'integer',
                    'min:1',
                    function (string $attribute, mixed $value, \Closure $fail) use ($batch): void {
                        if (!$batch instanceof InventoryBatch) {
                            return;
                        }

                        if ((int) $value < (int) $batch->reserved_quantity) {
                            $fail('تعداد موجودی نمی‌تواند کمتر از تعداد رزروشده باشد.');
                        }
                    },
                ],
                ...ValidationRules::description(),
            ];
        }
    }
