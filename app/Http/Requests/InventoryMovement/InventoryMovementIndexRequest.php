<?php

    namespace App\Http\Requests\InventoryMovement;

    use App\Enums\InventoryMovementType;
    use App\Http\Requests\IndexRequest;
    use Illuminate\Validation\Rule;

    class InventoryMovementIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'inventory_batch_id' => [
                    'nullable',
                    'string',
                    'exists:inventory_batches,public_id',
                ],
                'type' => [
                    'nullable',
                    Rule::enum(InventoryMovementType::class),
                ],
                'moved_from' => [
                    'nullable',
                    'date',
                ],
                'moved_to' => [
                    'nullable',
                    'date',
                    'after_or_equal:moved_from',
                ],
            ];
        }
    }
