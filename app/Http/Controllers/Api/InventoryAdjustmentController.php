<?php

    namespace App\Http\Controllers\Api;

    use App\Actions\Inventory\InventoryAdjustment\CreateInventoryAdjustmentAction;
    use App\Actions\Inventory\InventoryAdjustment\ListInventoryAdjustmentsAction;
    use App\Actions\Inventory\InventoryAdjustment\ShowInventoryAdjustmentAction;
    use App\Http\Controllers\Controller;
    use App\Http\Requests\InventoryAdjustment\InventoryAdjustmentIndexRequest;
    use App\Http\Requests\InventoryAdjustment\StoreInventoryAdjustmentRequest;
    use App\Http\Resources\InventoryAdjustmentResource;
    use App\Models\InventoryAdjustment;
    use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

    class InventoryAdjustmentController extends Controller {
        public function __construct() { $this->authorizeModel(InventoryAdjustment::class, 'inventoryAdjustment'); }

        public function index(InventoryAdjustmentIndexRequest $request,
            ListInventoryAdjustmentsAction $action): AnonymousResourceCollection {
            return InventoryAdjustmentResource::collection($action->execute($request->validated()));
        }

        public function show(InventoryAdjustment $inventoryAdjustment,
            ShowInventoryAdjustmentAction $action): InventoryAdjustmentResource {
            return new InventoryAdjustmentResource($action->execute($inventoryAdjustment));
        }

        public function store(StoreInventoryAdjustmentRequest $request,
            CreateInventoryAdjustmentAction $action): InventoryAdjustmentResource {
            return new InventoryAdjustmentResource($action->execute($request->validated()));
        }
    }
