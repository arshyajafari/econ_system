<?php

namespace App\Queries\ScientificVisitorInventory;

use App\Models\ScientificVisitorInventory;
use App\Queries\BaseQuery;

class ScientificVisitorInventoryQuery extends BaseQuery
{
    protected function initialize(): void
    {
        // The active inventory list is strictly for usable stock. Exhausted
        // aggregate rows remain in the database (and transfer ledger) for audit,
        // but must disappear immediately once available quantity reaches zero.
        $this->query = ScientificVisitorInventory::query()
            ->with(ScientificVisitorInventory::DEFAULT_RELATIONS)
            ->whereColumn('received_quantity', '>', 'used_quantity');
    }

    public function apply(array $filters, ?int $employeeId = null): static
    {
        if ($employeeId !== null) {
            $this->query->where('employee_id', $employeeId);
        }

        if (!empty($filters['employee_id'])) {
            $this->query->whereHas('employee', fn ($q) => $q->where('public_id', $filters['employee_id']));
        }

        if (!empty($filters['product_id'])) {
            $this->query->whereHas('product', fn ($q) => $q->where('public_id', $filters['product_id']));
        }

        if (filter_var($filters['available_only'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $this->query->whereColumn('received_quantity', '>', 'used_quantity');
        }

        $this->applySearch($filters['search'] ?? null, ['description']);
        $this->applySort($filters['sort'] ?? null, ['received_quantity', 'used_quantity', 'last_received_at', 'created_at'], 'last_received_at');

        return $this;
    }
}
