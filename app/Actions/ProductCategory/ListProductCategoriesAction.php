<?php

    namespace App\Actions\ProductCategory;

    use App\Queries\Product\ProductCategoryQuery;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;

    class ListProductCategoriesAction {
        public function execute(array $filters): LengthAwarePaginator {
            return ProductCategoryQuery::make()->apply($filters)->paginate($filters['per_page'] ?? 20);
        }
    }
