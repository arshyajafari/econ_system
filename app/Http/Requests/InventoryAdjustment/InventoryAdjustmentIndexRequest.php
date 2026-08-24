<?php

    namespace App\Http\Requests\InventoryAdjustment;

    use App\Enums\InventoryAdjustmentType;
    use App\Http\Requests\IndexRequest;
    use Illuminate\Validation\Rule;

    class InventoryAdjustmentIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'type' => [
                    'nullable',
                    Rule::enum(InventoryAdjustmentType::class),
                ],
                'inventory_batch_id' => [
                    'nullable',
                    'string',
                    'exists:inventory_batches,public_id',
                ],
            ];
        }
    }
