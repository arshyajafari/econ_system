<?php

namespace App\Http\Controllers\Api;

use App\Actions\Visit\CancelVisitAction;
use App\Actions\Visit\CompleteVisitAction;
use App\Actions\Visit\CreateVisitAction;
use App\Actions\Visit\CreateVisitWithSampleAction;
use App\Actions\Visit\ListVisitsAction;
use App\Actions\Visit\DeleteVisitAction;
use App\Actions\Visit\ReleaseVisitSamplesInventoryAction;
use App\Actions\Visit\ShowVisitAction;
use App\Actions\Visit\UpdateVisitAction;
use App\Actions\Visit\UpdateVisitWithSamplesAction;
use App\Http\Requests\Visit\UpdateVisitWithSamplesRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Visit\StoreVisitRequest;
use App\Http\Requests\Visit\StoreVisitWithSampleRequest;
use App\Http\Requests\Visit\UpdateVisitRequest;
use App\Http\Requests\Visit\VisitIndexRequest;
use App\Http\Resources\VisitResource;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class VisitController extends Controller {
    public function __construct() {
        $this->authorizeModel(Visit::class, 'visit');
    }

    public function index(VisitIndexRequest $request, ListVisitsAction $action) {
        $visits = $action->execute($request->validated(), $request->user());
        return VisitResource::collection($visits);
    }

    public function store(StoreVisitRequest $request, CreateVisitAction $action): JsonResponse {
        $visit = $action->execute($request->validated(), $request->user());
        return response()->json(new VisitResource($visit), 201);
    }

    public function storeWithSample(StoreVisitWithSampleRequest $request, CreateVisitWithSampleAction $action): JsonResponse {
        $visit = $action->execute($request->validated(), $request->user());
        return response()->json(new VisitResource($visit), 201);
    }

    public function show(Visit $visit, ShowVisitAction $action): VisitResource {
        return new VisitResource($action->execute($visit));
    }

    public function update(UpdateVisitRequest $request, Visit $visit, UpdateVisitAction $action): VisitResource {
        $this->authorize('update', $visit);
        return new VisitResource($action->execute($visit, $request->validated()));
    }

    public function updateWithSamples(UpdateVisitWithSamplesRequest $request, Visit $visit, UpdateVisitWithSamplesAction $action): VisitResource {
        $this->authorize('update', $visit);
        return new VisitResource($action->execute($visit, $request->validated(), $request->user()));
    }

    public function destroy(Visit $visit, DeleteVisitAction $action, ReleaseVisitSamplesInventoryAction $releaseInventory): Response {
        $this->authorize('delete', $visit);
        $action->execute($visit, $releaseInventory);
        return response()->noContent();
    }

    public function complete(Visit $visit, CompleteVisitAction $action): VisitResource {
        $this->authorize('complete', $visit);
        return new VisitResource($action->execute($visit));
    }

    public function cancel(Visit $visit, CancelVisitAction $action, ReleaseVisitSamplesInventoryAction $releaseInventory): VisitResource {
        $this->authorize('cancel', $visit);
        return new VisitResource($action->execute($visit, $releaseInventory));
    }
}
