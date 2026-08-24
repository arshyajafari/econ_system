<?php

    namespace App\Queries\Product;

    use App\Models\ProductCategory;
    use App\Queries\BaseQuery;
    use Illuminate\Database\Eloquent\Collection;

    class ProductCategoryQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = ProductCategory::query()->with(ProductCategory::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, ProductCategory::SEARCHABLE);
            $this->applyStatus($filters['status'] ?? null);
            $this->applyParent($filters['parent_id'] ?? null);
            $this->applySort($filters['sort'] ?? null, ProductCategory::SORTABLE, 'sort_order');

            return $this;
        }

        protected function applyStatus(?string $status): void {
            if (!$status) {
                return;
            }

            $this->query->where('status', $status);
        }

        protected function applyParent(?int $parentId): void {
            if ($parentId === null) {
                return;
            }

            $this->query->where('parent_id', $parentId);
        }
    }
