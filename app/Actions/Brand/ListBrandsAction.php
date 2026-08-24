<?php

    namespace App\Actions\Brand;

    use App\Queries\Brand\BrandQuery;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;

    class ListBrandsAction {
        public function execute(array $filters): LengthAwarePaginator {
            return BrandQuery::make()->apply($filters)->paginate($filters['per_page'] ?? 20);
        }
    }
