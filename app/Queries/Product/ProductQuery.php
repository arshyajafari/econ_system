<?php

    namespace App\Queries\Product;

    use App\Models\Brand;
    use App\Models\Product;
    use App\Models\ProductCategory;
    use App\Queries\BaseQuery;

    class ProductQuery extends BaseQuery {
        protected function initialize(): void {
            $this->query = Product::query()->with(Product::DEFAULT_RELATIONS);
        }

        public function apply(array $filters): static {
            $this->applySearch($filters['search'] ?? null, Product::SEARCHABLE);
            $this->applyBrand($filters['brand_id'] ?? null);
            $this->applyCategory($filters['product_category_id'] ?? null);
            $this->applyStatus($filters['status'] ?? null);
            $this->applySort($filters['sort'] ?? null, Product::SORTABLE, 'sort_order');

            return $this;
        }

        protected function applyBrand(?string $brandPublicId): void {
            if (!$brandPublicId) {
                return;
            }

            $brandId = Brand::query()->where('public_id', $brandPublicId)->value('id');

            if (!$brandId) {
                $this->query->whereRaw('1 = 0');

                return;
            }

            $this->query->where('brand_id', $brandId);
        }

        protected function applyCategory(?string $categoryPublicId): void {
            if (!$categoryPublicId) {
                return;
            }

            $categoryId = ProductCategory::query()->where('public_id', $categoryPublicId)->value('id');

            if (!$categoryId) {
                $this->query->whereRaw('1 = 0');

                return;
            }

            $this->query->where('product_category_id', $categoryId);
        }

        protected function applyStatus(?string $status): void {
            if (!$status) {
                return;
            }

            $this->query->where('status', $status);
        }
    }
