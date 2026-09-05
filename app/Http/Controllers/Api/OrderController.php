<?php

    namespace App\Http\Controllers\Api;

    use App\Actions\Order\CancelOrderAction;
    use App\Actions\Order\CompleteOrderAction;
    use App\Actions\Order\ConfirmOrderAction;
    use App\Actions\Order\CreateOrderAction;
    use App\Actions\Order\SubmitOrderAction;
    use App\Actions\Order\UpdateOrderAction;
    use App\Http\Controllers\Controller;
    use App\Http\Requests\Order\StoreOrderRequest;
    use App\Http\Requests\Order\UpdateOrderRequest;
    use App\Http\Resources\OrderResource;
    use App\Models\Order;
    use App\Queries\Order\OrderQuery;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;

    class OrderController extends Controller {
        public function __construct() {
            $this->authorizeModel(Order::class, 'order');
        }

        public function index(Request $request, OrderQuery $query) {
            $orders = $query->apply($request->all())->paginate($request->integer('per_page', 20));

            return OrderResource::collection($orders);
        }

        public function store(StoreOrderRequest $request, CreateOrderAction $action): JsonResponse {
            $order = $action->execute($request->validated());

            return response()->json(new OrderResource($order), 201);
        }

        public function show(Order $order): OrderResource {
            $order->load([
                ...Order::DEFAULT_RELATIONS,
                'items.allocations.inventoryBatch',
            ]);

            return new OrderResource($order);
        }

        public function update(UpdateOrderRequest $request, Order $order, UpdateOrderAction $action): OrderResource {
            $this->authorize('update', $order);

            $order = $action->execute($order, $request->validated());

            return new OrderResource($order);
        }

        public function submit(Order $order, SubmitOrderAction $action): OrderResource {
            $this->authorize('submit', $order);

            $order = $action->execute($order);

            return new OrderResource($order);
        }

        public function confirm(Order $order, ConfirmOrderAction $action): OrderResource {
            $this->authorize('confirm', $order);

            $order = $action->execute($order);

            return new OrderResource($order);
        }

        public function complete(Order $order, CompleteOrderAction $action): OrderResource {
            $this->authorize('complete', $order);

            $order = $action->execute($order);

            return new OrderResource($order);
        }

        public function cancel(Order $order, CancelOrderAction $action): OrderResource {
            $this->authorize('cancel', $order);

            $order = $action->execute($order);

            return new OrderResource($order);
        }
    }
