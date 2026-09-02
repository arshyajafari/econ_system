<?php

    namespace App\Http\Requests\InventoryAdjustment;

    use App\Enums\InventoryAdjustmentType;
    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;
    use Illuminate\Validation\Rule;

    class StoreInventoryAdjustmentRequest extends CrudRequest {
        public function rules(): array {
            return [
                'inventory_batch_id' => [
                    'required',
                    'string',
                    'exists:inventory_batches,public_id',
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
                    'required',
                    'string',
                    'max:300',
                ],
                ...ValidationRules::description(),
            ];
        }
    }
