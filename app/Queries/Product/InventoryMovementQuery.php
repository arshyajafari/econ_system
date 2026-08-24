<?php

    namespace App\Queries\Product;

    use App\Models\InventoryMovement;
    use App\Queries\BaseQuery;

    class InventoryMovementQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = InventoryMovement::query()->with(InventoryMovement::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, InventoryMovement::SEARCHABLE);
            $this->applyBatch($filters['inventory_batch_id'] ?? null);
            $this->applyType($filters['type'] ?? null);
            $this->applyDateRange($filters['moved_from'] ?? null, $filters['moved_to'] ?? null);
            $this->applySort($filters['sort'] ?? null, InventoryMovement::SORTABLE, 'moved_at');

            return $this;
        }

        protected function applyBatch(?int $batchId): void {
            if (!$batchId) {
                return;
            }

            $this->query->where('inventory_batch_id', $batchId);
        }

        protected function applyType(?string $type): void {
            if (!$type) {
                return;
            }

            $this->query->where('type', $type);
        }

        protected function applyDateRange(?string $from, ?string $to): void {
            if ($from) {
                $this->query->whereDate('moved_at', '>=', $from);
            }

            if ($to) {
                $this->query->whereDate('moved_at', '<=', $to);
            }
        }
    }
