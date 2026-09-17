<?php

namespace App\Http\Requests\InventoryBatch;

use App\Http\Requests\IndexRequest;

class InventoryBatchIndexRequest extends IndexRequest {
    protected function prepareForValidation(): void {
        if (!$this->has('expired')) {
            return;
        }

        $value = $this->input('expired');

        if ($value === 'true' || $value === '1') {
            $this->merge(['expired' => true]);
        } elseif ($value === 'false' || $value === '0') {
            $this->merge(['expired' => false]);
        }
    }

    public function rules(): array {
        return [
            ...$this->commonRules(),
            'product_id' => [
                'nullable',
                'string',
                'exists:products,public_id',
            ],
            'expired' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
