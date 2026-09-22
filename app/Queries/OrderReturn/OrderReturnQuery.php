<?php

namespace App\Queries\OrderReturn;

use App\Models\OrderReturn;
use App\Models\User;
use App\Queries\BaseQuery;

class OrderReturnQuery extends BaseQuery {
    protected function initialize(): void {
        $this->query = OrderReturn::query()->with([
            'order', 'customer', 'employee', 'items.orderItem', 'items.product',
        ]);
    }

    public function apply(array $filters, ?User $user = null): static {
        $user ??= auth()->user();
        $this->applySearch($filters['search'] ?? null, OrderReturn::SEARCHABLE);
        if (!empty($filters['order_id'])) {
            $this->query->whereHas('order', fn($query) => $query->where('public_id', $filters['order_id']));
        }
        if (!empty($filters['customer_id'])) {
            $this->query->whereHas('customer', fn($query) => $query->where('public_id', $filters['customer_id']));
        }

        // Admin/accountant can filter by employee. Delivery/settlement operators
        // must see the complete return register across all positions.
        if ($user?->hasAnyRole(['admin', 'accountant'])) {
            if (!empty($filters['employee_id'])) {
                $this->query->whereHas('employee', fn($query) => $query->where('public_id', $filters['employee_id']));
            }
        } elseif (!$user?->hasAnyRole(['delivery operator', 'settlement operator'])) {
            $employeeId = $user?->employee?->id;
            if ($employeeId) $this->query->where('employee_id', $employeeId);
            else $this->query->whereRaw('1 = 0');
        }

        $this->applyStatus($filters['status'] ?? null);
        $this->applyDateRange($filters['completed_from'] ?? null, $filters['completed_to'] ?? null);
        $this->applySort($filters['sort'] ?? null, OrderReturn::SORTABLE, 'created_at');
        return $this;
    }

    protected function applyStatus(?string $status): void { if ($status) $this->query->where('status', $status); }

    protected function applyDateRange(?string $from, ?string $to): void {
        if ($from) $this->query->whereDate('completed_at', '>=', $from);
        if ($to) $this->query->whereDate('completed_at', '<=', $to);
    }
}
