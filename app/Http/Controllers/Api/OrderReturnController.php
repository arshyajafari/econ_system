<?php

    namespace App\Http\Controllers\Api;

    use App\Actions\OrderReturn\CancelOrderReturnAction;
    use App\Actions\OrderReturn\CompleteOrderReturnAction;
    use App\Actions\OrderReturn\ConfirmOrderReturnAction;
    use App\Actions\OrderReturn\CreateOrderReturnAction;
    use App\Actions\OrderReturn\OrderReturnItem\AllocateOrderReturnItemAction;
    use App\Actions\OrderReturn\SubmitOrderReturnAction;
    use App\Actions\OrderReturn\UpdateOrderReturnAction;
    use App\Http\Controllers\Controller;
    use App\Http\Requests\OrderReturn\AllocateOrderReturnItemRequest;
    use App\Http\Requests\OrderReturn\OrderReturnIndexRequest;
    use App\Http\Requests\OrderReturn\StoreOrderReturnRequest;
    use App\Http\Requests\OrderReturn\UpdateOrderReturnRequest;
    use App\Http\Resources\OrderReturnItemResource;
    use App\Http\Resources\OrderReturnResource;
    use App\Models\OrderReturn;
    use App\Models\OrderReturnItem;
    use App\Queries\OrderReturn\OrderReturnQuery;
    use Illuminate\Http\JsonResponse;

    class OrderReturnController extends Controller {
        public function __construct() {
            $this->authorizeModel(OrderReturn::class, 'order_return');
        }

        public function index(OrderReturnIndexRequest $request, OrderReturnQuery $query) {
            $returns = $query->apply($request->validated())->paginate($request->integer('per_page', 20));

            return OrderReturnResource::collection($returns);
        }

        public function store(StoreOrderReturnRequest $request, CreateOrderReturnAction $action): JsonResponse {
            $orderReturn = $action->execute($request->validated(), $request->user());

            return response()->json(new OrderReturnResource($orderReturn), 201);
        }

        public function update(UpdateOrderReturnRequest $request, OrderReturn $orderReturn,
            UpdateOrderReturnAction $action): OrderReturnResource {
            $this->authorize('update', $orderReturn);

            $orderReturn = $action->execute($orderReturn, $request->validated());

            return new OrderReturnResource($orderReturn);
        }

        public function show(OrderReturn $orderReturn): OrderReturnResource {
            $orderReturn->load([
                ...OrderReturn::DEFAULT_RELATIONS,
                'items.orderItem',
                'items.allocations.inventoryBatch',
            ]);

            return new OrderReturnResource($orderReturn);
        }

        public function submit(OrderReturn $orderReturn, SubmitOrderReturnAction $action): OrderReturnResource {
            $this->authorize('submit', $orderReturn);

            $orderReturn = $action->execute($orderReturn);

            return new OrderReturnResource($orderReturn);
        }

        public function confirm(OrderReturn $orderReturn, ConfirmOrderReturnAction $action): OrderReturnResource {
            $this->authorize('confirm', $orderReturn);

            $orderReturn = $action->execute($orderReturn);

            return new OrderReturnResource($orderReturn);
        }

        public function complete(OrderReturn $orderReturn, CompleteOrderReturnAction $action): OrderReturnResource {
            $this->authorize('complete', $orderReturn);

            $orderReturn = $action->execute($orderReturn);

            return new OrderReturnResource($orderReturn);
        }

        public function cancel(OrderReturn $orderReturn, CancelOrderReturnAction $action): OrderReturnResource {
            $this->authorize('cancel', $orderReturn);

            $orderReturn = $action->execute($orderReturn);

            return new OrderReturnResource($orderReturn);
        }

        public function allocate(AllocateOrderReturnItemRequest $request, OrderReturnItem $orderReturnItem,
            AllocateOrderReturnItemAction $action): OrderReturnItemResource {
            $orderReturnItem->loadMissing('orderReturn');

            if (!$orderReturnItem->orderReturn) {
                abort(404, 'مرجوعی مربوط به این آیتم پیدا نشد.');
            }

            $this->authorize('allocate', $orderReturnItem->orderReturn);

            $orderReturnItem = $action->execute($orderReturnItem, $request->validated()['allocations']);

            return new OrderReturnItemResource($orderReturnItem);
        }
    }
