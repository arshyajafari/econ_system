<?php

namespace App\Queries\Product;

use App\Models\InventoryBatch;
use App\Queries\BaseQuery;

class InventoryBatchQuery extends BaseQuery
{
    protected function initialize(): void
    {
        $this->query = InventoryBatch::query()->with(InventoryBatch::DEFAULT_RELATIONS);
    }

    public function apply(array $filters): static
    {
        $this->applyInventorySearch($filters['search'] ?? null);
        $this->applyProduct($filters['product_id'] ?? null);
        $this->applyExpired($filters['expired'] ?? null);
        $this->applySort($filters['sort'] ?? null, InventoryBatch::SORTABLE, 'expire_date');

        return $this;
    }

    /**
     * Search both the batch number and its related product title.
     *
     * Keep these alternatives grouped so product-name matches remain
     * compatible with product, expiry, and sorting filters.
     */
    protected function applyInventorySearch(?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $term = "%{$search}%";

        $this->query->where(function ($query) use ($term) {
            $query->where('batch_number', 'like', $term)
                ->orWhereHas('product', function ($productQuery) use ($term) {
                    $productQuery->where('title', 'like', $term);
                });
        });
    }

    protected function applyProduct(?string $productPublicId): void
    {
        if (!$productPublicId) {
            return;
        }

        $this->query->whereHas('product', function ($query) use ($productPublicId) {
            $query->where('public_id', $productPublicId);
        });
    }

    protected function applyExpired(?bool $expired): void
    {
        if ($expired === null) {
            return;
        }

        if ($expired) {
            $this->query->whereDate('expire_date', '<', today());

            return;
        }

        $this->query->where(function ($query) {
            $query->whereNull('expire_date')->orWhereDate('expire_date', '>=', today());
        });
    }
}
