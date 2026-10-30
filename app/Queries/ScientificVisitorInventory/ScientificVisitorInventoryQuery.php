<?php

namespace App\Queries\ScientificVisitorInventory;

use App\Models\ScientificVisitorInventory;
use App\Queries\BaseQuery;

class ScientificVisitorInventoryQuery extends BaseQuery
{
    protected function initialize(): void
    {
        $this->query = ScientificVisitorInventory::query()
            ->with(ScientificVisitorInventory::DEFAULT_RELATIONS)
            // Keep exhausted inventory visible for 30 days after its last ledger
            // update (normally the transaction that consumes the final unit),
            // then hide it from this list without deleting its audit history.
            ->where(function ($query) {
                $query->whereColumn('received_quantity', '>', 'used_quantity')
                    ->orWhere('updated_at', '>=', now()->subDays(30));
            });
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
