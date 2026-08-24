<?php

    namespace App\Queries\Product;

    use App\Models\InventoryAdjustment;
    use App\Queries\BaseQuery;

    class InventoryAdjustmentQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = InventoryAdjustment::query()->with(InventoryAdjustment::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, InventoryAdjustment::SEARCHABLE);
            $this->applyType($filters['type'] ?? null);
            $this->applyBatch($filters['inventory_batch_id'] ?? null);
            $this->applySort($filters['sort'] ?? null, InventoryAdjustment::SORTABLE, 'created_at');

            return $this;
        }

        protected function applyType(?string $type): void {
            if (!$type) {
                return;
            }

            $this->query->where('type', $type);
        }

        protected function applyBatch(?int $batchId): void {
            if (!$batchId) {
                return;
            }

            $this->query->where('inventory_batch_id', $batchId);
        }
    }
