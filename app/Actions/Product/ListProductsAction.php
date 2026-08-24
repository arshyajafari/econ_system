<?php

    namespace App\Actions\Product;

    use App\Queries\Product\ProductQuery;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;

    class ListProductsAction {
        public function execute(array $filters): LengthAwarePaginator {
            return ProductQuery::make()->apply($filters)->paginate($filters['per_page'] ?? 20);
        }
    }
