<?php

    namespace App\Http\Controllers;

    use App\Actions\Inventory\InventoryAdjustment\CreateInventoryAdjustmentAction;
    use App\Actions\Inventory\InventoryAdjustment\ListInventoryAdjustmentsAction;
    use App\Actions\Inventory\InventoryAdjustment\ShowInventoryAdjustmentAction;
    use App\Http\Requests\InventoryAdjustment\CreateInventoryAdjustmentRequest;
    use App\Http\Requests\InventoryAdjustment\InventoryAdjustmentIndexRequest;
    use App\Http\Resources\InventoryAdjustmentResource;
    use App\Models\InventoryAdjustment;
    use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

    class InventoryAdjustmentController extends Controller {
        public function __construct() {
            $this->authorizeModel(InventoryAdjustment::class, 'inventoryAdjustment');
        }

        public function index(InventoryAdjustmentIndexRequest $request,
            ListInventoryAdjustmentsAction $action): AnonymousResourceCollection {
            return InventoryAdjustmentResource::collection($action->execute($request->validated()));
        }

        public function show(InventoryAdjustment $inventoryAdjustment,
            ShowInventoryAdjustmentAction $action): InventoryAdjustmentResource {
            return new InventoryAdjustmentResource($action->execute($inventoryAdjustment));
        }

        public function store(CreateInventoryAdjustmentRequest $request,
            CreateInventoryAdjustmentAction $action): InventoryAdjustmentResource {
            return new InventoryAdjustmentResource($action->execute($request->validated()));
        }
    }
