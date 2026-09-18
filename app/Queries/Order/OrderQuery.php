<?php

namespace App\Queries\Order;

use App\Models\Order;
use App\Models\User;
use App\Queries\BaseQuery;

class OrderQuery extends BaseQuery
{
    protected function initialize(): void
    {
        $this->query = Order::query()->with(Order::DEFAULT_RELATIONS);
    }

    public function apply(array $filters, ?User $user = null): static
    {
        $user ??= auth()->user();

        $this->applySearch($filters['search'] ?? null, Order::SEARCHABLE);
        $this->applyCustomer($filters['customer_id'] ?? null);

        if ($user?->hasRole('admin')) {
            $this->applySalesEmployee($filters['sales_employee_id'] ?? null);
        } else {
            $employeeId = $user?->employee?->id;

            if ($employeeId) {
                $this->query->where('sales_employee_id', $employeeId);
            } else {
                $this->query->whereRaw('1 = 0');
            }
        }

        $this->applyStatus($filters['status'] ?? null);
        $this->applyReturnable($filters['returnable'] ?? null);
        $this->applyInvoiceable($filters['invoiceable'] ?? null);
        $this->applyDeliverable($filters['deliverable'] ?? null);
        $this->applyDateRange($filters['ordered_from'] ?? null, $filters['ordered_to'] ?? null);
        $this->applySort($filters['sort'] ?? null, Order::SORTABLE, 'ordered_at');

        return $this;
    }

    protected function applyCustomer(?string $customerPublicId): void
    {
        if (!$customerPublicId) return;

        $this->query->whereHas('customer', fn($query) => $query->where('public_id', $customerPublicId));
    }

    protected function applySalesEmployee(?string $employeePublicId): void
    {
        if (!$employeePublicId) return;

        $this->query->whereHas('salesEmployee', fn($query) => $query->where('public_id', $employeePublicId));
    }

    protected function applyStatus(?string $status): void
    {
        if (!$status) return;
        $this->query->where('status', $status);
    }

    protected function applyReturnable(?bool $returnable): void
    {
        if ($returnable !== true) {
            return;
        }

        $this->query->where('status', \App\Enums\OrderStatus::COMPLETED)
            ->whereHas('items', function ($query) {
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

    protected function applyInvoiceable(?bool $invoiceable): void
    {
        if ($invoiceable !== true) {
            return;
        }

        $this->query
            ->where('status', \App\Enums\OrderStatus::PENDING)
            ->whereDoesntHave('invoice');
    }

    protected function applyDeliverable(?bool $deliverable): void
    {
        if ($deliverable !== true) {
            return;
        }

        $this->query
            ->where('status', \App\Enums\OrderStatus::PENDING)
            ->whereDoesntHave('delivery');
    }

    protected function applyDateRange(?string $from, ?string $to): void
    {
        if ($from) $this->query->whereDate('ordered_at', '>=', $from);
        if ($to) $this->query->whereDate('ordered_at', '<=', $to);
    }
}
