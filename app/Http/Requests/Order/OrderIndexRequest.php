<?php

    namespace App\Http\Requests\Order;

    use App\Enums\OrderStatus;
    use App\Http\Requests\IndexRequest;
    use Illuminate\Validation\Rule;

    class OrderIndexRequest extends IndexRequest {
        protected function prepareForValidation(): void {
            $booleans = ['returnable', 'invoiceable', 'deliverable'];
            $normalized = [];

            foreach ($booleans as $field) {
                if (!$this->has($field)) continue;

                $value = $this->input($field);
                if ($value === true || $value === false) {
                    $normalized[$field] = $value;
                    continue;
                }

                if (is_string($value)) {
                    $normalized[$field] = match (strtolower($value)) {
                        'true', '1' => true,
                        'false', '0' => false,
                        default => $value,
                    };
                    continue;
                }

                if (is_numeric($value) && ((int) $value === 0 || (int) $value === 1)) {
                    $normalized[$field] = (bool) $value;
                }
            }

            if ($normalized !== []) {
                $this->merge($normalized);
            }
        }

        public function rules(): array {
            return [
                ...$this->commonRules(),
                'customer_id' => [
                    'nullable',
                    'string',
                    'exists:customers,public_id',
                ],
                'sales_employee_id' => [
                    'nullable',
                    'string',
                    'exists:employees,public_id',
                ],
                'status' => [
                    'nullable',
                    Rule::enum(OrderStatus::class),
                ],
                'returnable' => [
                    'nullable',
                    'boolean',
                ],
                'invoiceable' => [
                    'nullable',
                    'boolean',
                ],
                'deliverable' => [
                    'nullable',
                    'boolean',
                ],
                'ordered_from' => [
                    'nullable',
                    'date',
                ],
                'ordered_to' => [
                    'nullable',
                    'date',
                    'after_or_equal:ordered_from',
                ],
            ];
        }
    }
