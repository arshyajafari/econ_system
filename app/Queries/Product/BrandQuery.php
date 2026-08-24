<?php

    namespace App\Queries\Brand;

    use App\Models\Brand;
    use App\Queries\BaseQuery;

    class BrandQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = Brand::query()->with(Brand::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, Brand::SEARCHABLE);
            $this->applyActivity($filters['is_active'] ?? null);
            $this->applySort($filters['sort'] ?? null, Brand::SORTABLE, 'sort_order');

            return $this;
        }

        protected function applyActivity(?bool $isActive): void {
            if ($isActive === null) {
                return;
            }

            $this->query->where('is_active', $isActive);
        }
    }
