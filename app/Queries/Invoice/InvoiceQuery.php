<?php

    namespace App\Queries\Invoice;

    use App\Models\Invoice;
    use App\Queries\BaseQuery;

    class InvoiceQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = Invoice::query()->with(Invoice::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, Invoice::SEARCHABLE);
            $this->applyOrder($filters['order_id'] ?? null);
            $this->applyCustomer($filters['customer_id'] ?? null);
            $this->applyEmployee($filters['employee_id'] ?? null);
            $this->applyStatus($filters['status'] ?? null);
            $this->applyDateRange($filters['issued_from'] ?? null, $filters['issued_to'] ?? null);
            $this->applySort($filters['sort'] ?? null, Invoice::SORTABLE, 'created_at');

            return $this;
        }

        protected function applyOrder(?string $orderId): void {
            if (!$orderId) {
                return;
            }

            $this->query->whereHas('order', function ($query) use ($orderId) {
                $query->where('public_id', $orderId);
            });
        }

        protected function applyCustomer(?string $customerId): void {
            if (!$customerId) {
                return;
            }

            $this->query->whereHas('customer', function ($query) use ($customerId) {
                $query->where('public_id', $customerId);
            });
        }

        protected function applyEmployee(?string $employeeId): void {
            if (!$employeeId) {
                return;
            }

            $this->query->whereHas('employee', function ($query) use ($employeeId) {
                $query->where('public_id', $employeeId);
            });
        }

        protected function applyStatus(?string $status): void {
            if (!$status) {
                return;
            }

            $this->query->where('status', $status);
        }

        protected function applyDateRange(?string $from, ?string $to): void {
            if ($from) {
                $this->query->whereDate('issued_at', '>=', $from);
            }

            if ($to) {
                $this->query->whereDate('issued_at', '<=', $to);
            }
        }
    }
