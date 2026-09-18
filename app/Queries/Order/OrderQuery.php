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

    protected function applyDateRange(?string $from, ?string $to): void
    {
        if ($from) $this->query->whereDate('ordered_at', '>=', $from);
        if ($to) $this->query->whereDate('ordered_at', '<=', $to);
    }
}
