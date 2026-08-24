<?php

    namespace App\Actions\Inventory\InventoryBatch;

    use App\Queries\Product\InventoryBatchQuery;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;

    class ListInventoryBatchesAction {
        public function execute(array $filters): LengthAwarePaginator {
            return InventoryBatchQuery::make()->apply($filters)->paginate($filters['per_page'] ?? 20);
        }
    }
