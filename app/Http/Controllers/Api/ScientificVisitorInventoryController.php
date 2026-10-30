<?php

namespace App\Http\Controllers\Api;

use App\Actions\ScientificVisitorInventory\CreateScientificVisitorInventoryAction;
use App\Actions\ScientificVisitorInventory\DeleteScientificVisitorInventoryAction;
use App\Actions\ScientificVisitorInventory\ListScientificVisitorInventoryAction;
use App\Actions\ScientificVisitorInventory\UpdateScientificVisitorInventoryAction;
use App\Actions\ScientificVisitorInventory\UpdateScientificVisitorInventoryTransferAction;
use App\Actions\ScientificVisitorInventory\DeleteScientificVisitorInventoryTransferAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScientificVisitorInventory\ScientificVisitorInventoryIndexRequest;
use App\Http\Requests\ScientificVisitorInventory\StoreScientificVisitorInventoryRequest;
use App\Http\Requests\ScientificVisitorInventory\UpdateScientificVisitorInventoryRequest;
use App\Http\Resources\ScientificVisitorInventoryResource;
use App\Models\ScientificVisitorInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function transfers(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ScientificVisitorInventory::class);
        $query = DB::table('scientific_visitor_inventory_transfers as t')
            ->join('scientific_visitor_inventories as svi', 'svi.id', '=', 't.scientific_visitor_inventory_id')
            ->join('employees as e', 'e.id', '=', 'svi.employee_id')
            ->join('products as p', 'p.id', '=', 'svi.product_id')
            ->select(['t.id', 't.quantity', 't.used_quantity', 't.returned_quantity', 't.created_at as delivered_at', 't.updated_at', 'svi.public_id as inventory_id', 'e.public_id as employee_id', 'e.code as employee_code', 'e.full_name as employee_name', 'p.public_id as product_id', 'p.code as product_code', 'p.title as product_title']);
        if ($request->user()->hasRole('scientific visitor')) { $query->where('svi.employee_id', $request->user()->employee?->id ?? -1); }
        elseif ($request->filled('employee_id')) { $query->where('e.public_id', $request->input('employee_id')); }
        if ($request->filled('product_id')) $query->where('p.public_id', $request->input('product_id'));
        $rows = $query->orderByDesc('t.created_at')->orderByDesc('t.id')->paginate((int) $request->input('per_page', 20));
        $rows->getCollection()->transform(function ($row) {
            $row->available_quantity = max(0, (int) $row->quantity - (int) $row->used_quantity - (int) $row->returned_quantity);
            $row->received_quantity = (int) $row->quantity - (int) $row->returned_quantity;
            return $row;
        });
        return response()->json($rows);
    }

    public function updateTransfer(Request $request, ScientificVisitorInventory $scientificVisitorInventory, int $transfer, UpdateScientificVisitorInventoryTransferAction $action): JsonResponse
    {
        $this->authorize('update', $scientificVisitorInventory);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:1000000'], 'inventory_batch_id' => ['nullable', 'string', 'exists:inventory_batches,public_id'], 'description' => ['nullable', 'string']]);
        return response()->json($action->execute($scientificVisitorInventory, $transfer, $data));
    }

    public function destroyTransfer(ScientificVisitorInventory $scientificVisitorInventory, int $transfer, DeleteScientificVisitorInventoryTransferAction $action): JsonResponse
    {
        $this->authorize('delete', $scientificVisitorInventory);
        $action->execute($scientificVisitorInventory, $transfer);
        return response()->json(null, 204);
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
