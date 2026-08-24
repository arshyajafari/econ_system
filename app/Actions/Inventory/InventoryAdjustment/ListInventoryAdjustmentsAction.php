<?php

    namespace App\Actions\Inventory\InventoryAdjustment;

    use App\Queries\Product\InventoryAdjustmentQuery;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;

    class ListInventoryAdjustmentsAction {
        public function execute(array $filters): LengthAwarePaginator {
            return InventoryAdjustmentQuery::make()->apply($filters)->paginate($filters['per_page'] ?? 20);
        }
    }
