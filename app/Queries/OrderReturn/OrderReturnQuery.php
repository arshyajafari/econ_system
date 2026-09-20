<?php

namespace App\Queries\OrderReturn;

use App\Models\OrderReturn;
use App\Models\User;
use App\Queries\BaseQuery;

class OrderReturnQuery extends BaseQuery
{
    protected function initialize(): void
    {
        $this->query = OrderReturn::query()->with([
            'order',
            'customer',
            'employee',
            'items.orderItem',
            'items.product',
        ]);
    }

    public function apply(array $filters, ?User $user = null): static
    {
        $user ??= auth()->user();

        $this->applySearch($filters['search'] ?? null, OrderReturn::SEARCHABLE);

        if (!empty($filters['order_id'])) {
            $this->query->whereHas('order', fn($query) => $query->where('public_id', $filters['order_id']));
        }

        if (!empty($filters['customer_id'])) {
            $this->query->whereHas('customer', fn($query) => $query->where('public_id', $filters['customer_id']));
        }

        if ($user?->hasAnyRole(['admin', 'accountant'])) {
            if (!empty($filters['employee_id'])) {
                $this->query->whereHas('employee', fn($query) => $query->where('public_id', $filters['employee_id']));
            }
        } else {
            $employeeId = $user?->employee?->id;

            if ($employeeId) {
                $this->query->where('employee_id', $employeeId);
            } else {
                $this->query->whereRaw('1 = 0');
            }
        }

        $this->applyStatus($filters['status'] ?? null);
        $this->applyReturnableOrder();
        $this->applyDateRange($filters['completed_from'] ?? null, $filters['completed_to'] ?? null);
        $this->applySort($filters['sort'] ?? null, OrderReturn::SORTABLE, 'created_at');

        return $this;
    }

    protected function applyStatus(?string $status): void
    {
        if (!$status) return;
        $this->query->where('status', $status);
    }

    protected function applyReturnableOrder(): void
    {
        $this->query->whereHas('order.items', function ($query) {
            $query->whereRaw(
                'order_items.quantity > (
                    SELECT COALESCE(SUM(order_return_items.quantity), 0)
                    FROM order_return_items
                    INNER JOIN order_returns ON order_returns.id = order_return_items.order_return_id
                    WHERE order_return_items.order_item_id = order_items.id
                      AND order_returns.status NOT IN (?, ?)
                      AND order_returns.deleted_at IS NULL
                      AND order_return_items.deleted_at IS NULL
                )',
                ['draft', 'cancelled'],
            );
        });
    }

    protected function applyDateRange(?string $from, ?string $to): void
    {
        if ($from) $this->query->whereDate('completed_at', '>=', $from);
        if ($to) $this->query->whereDate('completed_at', '<=', $to);
    }
}
