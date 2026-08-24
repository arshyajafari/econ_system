<?php

    namespace App\Http\Controllers;

    use App\Actions\Inventory\InventoryBatch\CreateInventoryBatchAction;
    use App\Actions\Inventory\InventoryBatch\DeleteInventoryBatchAction;
    use App\Actions\Inventory\InventoryBatch\ListInventoryBatchesAction;
    use App\Actions\Inventory\InventoryBatch\ShowInventoryBatchAction;
    use App\Actions\Inventory\InventoryBatch\UpdateInventoryBatchAction;
    use App\Http\Requests\InventoryBatch\InventoryBatchIndexRequest;
    use App\Http\Requests\InventoryBatch\StoreInventoryBatchRequest;
    use App\Http\Requests\InventoryBatch\UpdateInventoryBatchRequest;
    use App\Http\Resources\InventoryBatch\InventoryBatchResource;
    use App\Models\InventoryBatch;
    use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
    use Symfony\Component\HttpFoundation\Response;

    class InventoryBatchController extends Controller {
        public function __construct() {
            $this->authorizeModel(InventoryBatch::class, 'inventoryBatch');
        }

        public function index(InventoryBatchIndexRequest $request,
            ListInventoryBatchesAction $action): AnonymousResourceCollection {
            return InventoryBatchResource::collection($action->execute($request->validated()));
        }

        public function show(InventoryBatch $inventoryBatch, ShowInventoryBatchAction $action): InventoryBatchResource {
            return new InventoryBatchResource($action->execute($inventoryBatch));
        }

        public function store(StoreInventoryBatchRequest $request,
            CreateInventoryBatchAction $action): InventoryBatchResource {
            return new InventoryBatchResource($action->execute($request->validated()));
        }

        public function update(UpdateInventoryBatchRequest $request, InventoryBatch $inventoryBatch,
            UpdateInventoryBatchAction $action): InventoryBatchResource {
            return new InventoryBatchResource($action->execute($inventoryBatch, $request->validated()));
        }

        public function destroy(InventoryBatch $inventoryBatch, DeleteInventoryBatchAction $action): Response {
            $action->execute($inventoryBatch);

            return response()->noContent();
        }
    }
