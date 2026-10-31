<?php

namespace App\Http\Controllers\Api;

use App\Actions\ScientificVisitorInventory\CreateScientificVisitorInventoryAction;
use App\Actions\ScientificVisitorInventory\DeleteScientificVisitorInventoryAction;
use App\Actions\ScientificVisitorInventory\ListScientificVisitorInventoryAction;
use App\Actions\ScientificVisitorInventory\ListScientificVisitorInventoryTransfersAction;
use App\Actions\ScientificVisitorInventory\UpdateScientificVisitorInventoryTransferAction;
use App\Actions\ScientificVisitorInventory\DeleteScientificVisitorInventoryTransferAction;
use App\Actions\ScientificVisitorInventory\UpdateScientificVisitorInventoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScientificVisitorInventory\ScientificVisitorInventoryIndexRequest;
use App\Http\Requests\ScientificVisitorInventory\StoreScientificVisitorInventoryRequest;
use App\Http\Requests\ScientificVisitorInventory\UpdateScientificVisitorInventoryRequest;
use App\Http\Resources\ScientificVisitorInventoryResource;
use App\Models\ScientificVisitorInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ScientificVisitorInventoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeModel(ScientificVisitorInventory::class, 'scientificVisitorInventory');
    }

    public function index(
        ScientificVisitorInventoryIndexRequest $request,
        ListScientificVisitorInventoryAction $action
    ): AnonymousResourceCollection {
        return ScientificVisitorInventoryResource::collection(
            $action->execute($request->validated(), $request->user())
        );
    }

    public function transfers(ScientificVisitorInventoryIndexRequest $request, ListScientificVisitorInventoryTransfersAction $action): JsonResponse
    {
        $page = $action->execute($request->validated(), $request->user());
        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
                'per_page' => $page->perPage(),
            ],
        ]);
    }

    public function updateTransfer(
        UpdateScientificVisitorInventoryRequest $request,
        ScientificVisitorInventory $scientificVisitorInventory,
        int $transferId,
        UpdateScientificVisitorInventoryTransferAction $action
    ): JsonResponse {
        $this->authorize('update', $scientificVisitorInventory);
        $transfer = $action->execute($scientificVisitorInventory, $transferId, $request->validated());
        return response()->json(['data' => $transfer]);
    }

    public function destroyTransfer(
        ScientificVisitorInventory $scientificVisitorInventory,
        int $transferId,
        DeleteScientificVisitorInventoryTransferAction $action
    ): JsonResponse {
        $this->authorize('delete', $scientificVisitorInventory);
        $action->execute($scientificVisitorInventory, $transferId);
        return response()->json(null, 204);
    }

    public function store(
        StoreScientificVisitorInventoryRequest $request,
        CreateScientificVisitorInventoryAction $action
    ): JsonResponse {
        return response()->json(new ScientificVisitorInventoryResource(
            $action->execute($request->validated())
        ), 201);
    }

    public function update(
        UpdateScientificVisitorInventoryRequest $request,
        ScientificVisitorInventory $scientificVisitorInventory,
        UpdateScientificVisitorInventoryAction $action
    ): ScientificVisitorInventoryResource {
        $this->authorize('update', $scientificVisitorInventory);

        return new ScientificVisitorInventoryResource(
            $action->execute($scientificVisitorInventory, $request->validated())
        );
    }

    public function destroy(
        ScientificVisitorInventory $scientificVisitorInventory,
        DeleteScientificVisitorInventoryAction $action
    ): JsonResponse {
        $this->authorize('delete', $scientificVisitorInventory);

        $action->execute($scientificVisitorInventory);

        return response()->json(null, 204);
    }
}
