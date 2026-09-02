<?php

    namespace App\Http\Controllers;

    use App\Actions\Inventory\InventoryMovement\ListInventoryMovementsAction;
    use App\Actions\Inventory\InventoryMovement\ShowInventoryMovementAction;
    use App\Http\Requests\InventoryMovement\InventoryMovementIndexRequest;
    use App\Http\Resources\InventoryMovementResource;
    use App\Models\InventoryMovement;
    use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

    class InventoryMovementController extends Controller {
        public function __construct() {
            $this->authorizeModel(InventoryMovement::class, 'inventoryMovement');
        }

        public function index(InventoryMovementIndexRequest $request,
            ListInventoryMovementsAction $action): AnonymousResourceCollection {
            return InventoryMovementResource::collection($action->execute($request->validated()));
        }

        public function show(InventoryMovement $inventoryMovement,
            ShowInventoryMovementAction $action): InventoryMovementResource {
            return new InventoryMovementResource($action->execute($inventoryMovement));
        }
    }
