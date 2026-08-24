<?php

    namespace App\Http\Requests\InventoryAdjustment;

    use App\Enums\InventoryAdjustmentType;
    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;
    use Illuminate\Validation\Rule;

    class CreateInventoryAdjustmentRequest extends CrudRequest {
        public function rules(): array {
            return [
                'inventory_batch_id' => [
                    'required',
                    'integer',
                    'exists:inventory_batches,id',
                ],
                'type' => [
                    'required',
                    Rule::enum(InventoryAdjustmentType::class),
                ],
                'quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],
                'reason' => [
                    'nullable',
                    'string',
                    'max:300',
                ],
                ...ValidationRules::description(),
            ];
        }
    }
