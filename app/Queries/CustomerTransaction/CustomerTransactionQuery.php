<?php

    namespace App\Queries\CustomerTransaction;

    use App\Models\CustomerTransaction;
    use App\Queries\BaseQuery;

    class CustomerTransactionQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = CustomerTransaction::query()->with(CustomerTransaction::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, CustomerTransaction::SEARCHABLE);
            $this->applyCustomer($filters['customer_id'] ?? null);
            $this->applyType($filters['type'] ?? null);
            $this->applyDateRange($filters['transaction_from'] ?? null, $filters['transaction_to'] ?? null);
            $this->applySort($filters['sort'] ?? null, CustomerTransaction::SORTABLE, 'transaction_at');

            return $this;
        }

        protected function applyCustomer(?string $customerId): void {
            if (!$customerId) {
                return;
            }

            $this->query->whereHas('customer', function ($query) use ($customerId) {
                $query->where('public_id', $customerId);
            });
        }

        protected function applyType(?string $type): void {
            if (!$type) {
                return;
            }

            $this->query->where('type', $type);
        }

        protected function applyDateRange(?string $from, ?string $to): void {
            if ($from) {
                $this->query->whereDate('transaction_at', '>=', $from);
            }

            if ($to) {
                $this->query->whereDate('transaction_at', '<=', $to);
            }
        }
    }
