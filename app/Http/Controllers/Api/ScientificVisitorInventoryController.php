<?php

namespace App\Http\Controllers\Api;

use App\Actions\ScientificVisitorInventory\CreateScientificVisitorInventoryAction;
use App\Actions\ScientificVisitorInventory\ListScientificVisitorInventoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScientificVisitorInventory\ScientificVisitorInventoryIndexRequest;
use App\Http\Requests\ScientificVisitorInventory\StoreScientificVisitorInventoryRequest;
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

    public function store(
        StoreScientificVisitorInventoryRequest $request,
        CreateScientificVisitorInventoryAction $action
    ): JsonResponse {
        return response()->json(new ScientificVisitorInventoryResource(
            $action->execute($request->validated())
        ), 201);
    }
}