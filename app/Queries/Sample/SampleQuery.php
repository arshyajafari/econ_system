<?php

namespace App\Queries\Sample;

use App\Models\Sample;
use App\Queries\BaseQuery;

class SampleQuery extends BaseQuery
{
    protected function initialize(): void
    {
        $this->query = Sample::query()->with(Sample::DEFAULT_RELATIONS);
    }

    public function apply(array $filters, ?int $employeeId = null): static
    {
        if ($employeeId !== null) {
            $this->query->whereHas('visit', fn ($query) => $query->where('employee_id', $employeeId));
        }

        $this->applySearch($filters['search'] ?? null, Sample::SEARCHABLE);
        $this->applyVisit($filters['visit_id'] ?? null);
        $this->applyProduct($filters['product_id'] ?? null);
        $this->applySort($filters['sort'] ?? null, Sample::SORTABLE, 'created_at');

        return $this;
    }

    protected function applyVisit(?string $visitId): void
    {
        if (!$visitId) return;

        $this->query->whereHas('visit', fn ($query) => $query->where('public_id', $visitId));
    }

    protected function applyProduct(?string $productId): void
    {
        if (!$productId) return;

        $this->query->whereHas('product', fn ($query) => $query->where('public_id', $productId));
    }
}