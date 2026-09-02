<?php

    namespace App\Queries\Product;

    use App\Models\InventoryAdjustment;
    use App\Queries\BaseQuery;
    use Illuminate\Database\Eloquent\Builder;

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

        protected function applyBatch(?string $batchPublicId): void {
            if (!$batchPublicId) {
                return;
            }
            $this->query->whereHas('inventoryBatch',
                function (Builder $query) use ($batchPublicId) { $query->where('public_id', $batchPublicId); });
        }
    }
