<?php

    namespace App\Http\Controllers;

    use App\Actions\Delivery\CancelDeliveryAction;
    use App\Actions\Delivery\CompleteDeliveryAction;
    use App\Actions\Delivery\CreateDeliveryAction;
    use App\Actions\Delivery\PrepareDeliveryAction;
    use App\Actions\Delivery\ShipDeliveryAction;
    use App\Actions\Delivery\UpdateDeliveryAction;
    use App\Http\Requests\Delivery\DeliveryIndexRequest;
    use App\Http\Requests\Delivery\StoreDeliveryRequest;
    use App\Http\Requests\Delivery\UpdateDeliveryRequest;
    use App\Http\Resources\DeliveryResource;
    use App\Models\Delivery;
    use App\Queries\Delivery\DeliveryQuery;
    use Illuminate\Http\JsonResponse;

    class DeliveryController extends Controller {
        public function __construct() {
            $this->authorizeModel(Delivery::class, 'delivery');
        }

        public function index(DeliveryIndexRequest $request, DeliveryQuery $query) {
            $deliveries = $query->apply($request->validated())->paginate($request->integer('per_page', 20));

            return DeliveryResource::collection($deliveries);
        }

        public function store(StoreDeliveryRequest $request, CreateDeliveryAction $action): JsonResponse {
            $delivery = $action->execute($request->validated(), $request->user());

            return response()->json(new DeliveryResource($delivery), 201);
        }

        public function show(Delivery $delivery): DeliveryResource {
            $delivery->load(Delivery::DEFAULT_RELATIONS);

            return new DeliveryResource($delivery);
        }

        public function update(UpdateDeliveryRequest $request, Delivery $delivery,
            UpdateDeliveryAction $action): DeliveryResource {
            $this->authorize('update', $delivery);

            $delivery = $action->execute($delivery, $request->validated());

            return new DeliveryResource($delivery);
        }

        public function prepare(Delivery $delivery, PrepareDeliveryAction $action): DeliveryResource {
            $this->authorize('prepare', $delivery);

            $delivery = $action->execute($delivery);

            return new DeliveryResource($delivery);
        }

        public function ship(Delivery $delivery, ShipDeliveryAction $action): DeliveryResource {
            $this->authorize('ship', $delivery);

            $delivery = $action->execute($delivery);

            return new DeliveryResource($delivery);
        }

        public function complete(Delivery $delivery, CompleteDeliveryAction $action): DeliveryResource {
            $this->authorize('complete', $delivery);

            $delivery = $action->execute($delivery);

            return new DeliveryResource($delivery);
        }

        public function cancel(Delivery $delivery, CancelDeliveryAction $action): DeliveryResource {
            $this->authorize('cancel', $delivery);

            $delivery = $action->execute($delivery);

            return new DeliveryResource($delivery);
        }
    }
