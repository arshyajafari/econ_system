<?php

    namespace App\Queries\Customer;

    use App\Models\Customer;
    use App\Queries\BaseQuery;

    class CustomerQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = Customer::query()->with(Customer::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, Customer::SEARCHABLE);
            $this->applyType($filters['type'] ?? null);
            $this->applyStatus($filters['status'] ?? null);
            $this->applySort($filters['sort'] ?? null, Customer::SORTABLE, 'customer_name');

            return $this;
        }

        protected function applyType(?string $type): void {
            if (!$type) {
                return;
            }

            $this->query->where('type', $type);
        }

        protected function applyStatus(?string $status): void {
            if (!$status) {
                return;
            }

            $this->query->where('status', $status);
        }
    }
