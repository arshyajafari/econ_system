<?php

    namespace App\Queries\Product;

    use App\Models\InventoryBatch;
    use App\Queries\BaseQuery;

    class InventoryBatchQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = InventoryBatch::query()->with(InventoryBatch::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, InventoryBatch::SEARCHABLE);
            $this->applyProduct($filters['product_id'] ?? null);
            $this->applyExpired($filters['expired'] ?? null);
            $this->applySort($filters['sort'] ?? null, InventoryBatch::SORTABLE, 'expire_date');

            return $this;
        }

        protected function applyProduct(?int $productId): void {
            if (!$productId) {
                return;
            }

            $this->query->where('product_id', $productId);
        }

        protected function applyExpired(?bool $expired): void {
            if ($expired === null) {
                return;
            }

            if ($expired) {
                $this->query->whereDate('expire_date', '<', now());

                return;
            }

            $this->query->where(function ($query) {
                $query->whereNull('expire_date')->orWhereDate('expire_date', '>=', now());
            });
        }
    }
