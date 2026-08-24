<?php

    namespace App\Queries\OrderReturn;

    use App\Models\OrderReturn;
    use App\Queries\BaseQuery;

    class OrderReturnQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = OrderReturn::query()->with([
                'order',
                'customer',
                'employee',
                'items.orderItem',
                'items.product',
            ]);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, OrderReturn::SEARCHABLE);

            if (!empty($filters['order_id'])) {
                $this->query->whereHas('order', function ($query) use ($filters) {
                    $query->where('public_id', $filters['order_id']);
                });
            }

            if (!empty($filters['customer_id'])) {
                $this->query->whereHas('customer', function ($query) use ($filters) {
                    $query->where('public_id', $filters['customer_id']);
                });
            }

            if (!empty($filters['employee_id'])) {
                $this->query->whereHas('employee', function ($query) use ($filters) {
                    $query->where('public_id', $filters['employee_id']);
                });
            }

            $this->applyStatus($filters['status'] ?? null);

            $this->applyDateRange($filters['completed_from'] ?? null, $filters['completed_to'] ?? null);

            $this->applySort($filters['sort'] ?? null, OrderReturn::SORTABLE, 'created_at');

            return $this;
        }

        protected function applyStatus(?string $status): void {
            if (!$status) {
                return;
            }

            $this->query->where('status', $status);
        }

        protected function applyDateRange(?string $from, ?string $to): void {
            if ($from) {
                $this->query->whereDate('completed_at', '>=', $from);
            }

            if ($to) {
                $this->query->whereDate('completed_at', '<=', $to);
            }
        }
    }
