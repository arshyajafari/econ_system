<?php

    namespace App\Queries\Order;

    use App\Models\Order;
    use App\Queries\BaseQuery;

    class OrderQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = Order::query()->with(Order::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, Order::SEARCHABLE);
            $this->applyCustomer($filters['customer_id'] ?? null);
            $this->applySalesEmployee($filters['sales_employee_id'] ?? null);
            $this->applyStatus($filters['status'] ?? null);
            $this->applyDateRange($filters['ordered_from'] ?? null, $filters['ordered_to'] ?? null);
            $this->applySort($filters['sort'] ?? null, Order::SORTABLE, 'ordered_at');

            return $this;
        }

        protected function applyCustomer(?string $customerPublicId): void {
            if (!$customerPublicId) {
                return;
            }

            $this->query->whereHas('customer', function ($query) use ($customerPublicId) {
                $query->where('public_id', $customerPublicId);
            });
        }

        protected function applySalesEmployee(?string $employeePublicId): void {
            if (!$employeePublicId) {
                return;
            }

            $this->query->whereHas('salesEmployee', function ($query) use ($employeePublicId) {
                $query->where('public_id', $employeePublicId);
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
                $this->query->whereDate('ordered_at', '>=', $from);
            }

            if ($to) {
                $this->query->whereDate('ordered_at', '<=', $to);
            }
        }
    }
