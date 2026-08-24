<?php

    namespace App\Actions\Inventory\InventoryMovement;

    use App\Queries\Product\InventoryMovementQuery;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;

    class ListInventoryMovementsAction {
        public function execute(array $filters): LengthAwarePaginator {
            return InventoryMovementQuery::make()->apply($filters)->paginate($filters['per_page'] ?? 20);
        }
    }
