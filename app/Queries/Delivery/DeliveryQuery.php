<?php

    namespace App\Queries\Delivery;

    use App\Models\Delivery;
    use App\Queries\BaseQuery;

    class DeliveryQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = Delivery::query()->with(Delivery::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, Delivery::SEARCHABLE);
            $this->applyOrder($filters['order_id'] ?? null);
            $this->applyCustomer($filters['customer_id'] ?? null);
            $this->applyEmployee($filters['employee_id'] ?? null);
            $this->applyStatus($filters['status'] ?? null);
            $this->applySort($filters['sort'] ?? null, Delivery::SORTABLE, 'created_at');

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
    }
