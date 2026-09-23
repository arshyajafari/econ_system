<?php

namespace App\Http\Controllers\Api;

use App\Actions\OrderReturn\AllocateOrderReturnItemAction;
use App\Actions\OrderReturn\CancelOrderReturnAction;
use App\Actions\OrderReturn\CompleteOrderReturnAction;
use App\Actions\OrderReturn\ConfirmOrderReturnAction;
use App\Actions\OrderReturn\CreateOrderReturnAction;
use App\Actions\OrderReturn\ReceiveOrderReturnAction;
use App\Actions\OrderReturn\SubmitOrderReturnAction;
use App\Actions\OrderReturn\UpdateOrderReturnAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderReturn\AllocateOrderReturnItemRequest;
use App\Http\Requests\OrderReturn\OrderReturnIndexRequest;
use App\Http\Requests\OrderReturn\StoreOrderReturnRequest;
use App\Http\Requests\OrderReturn\UpdateOrderReturnRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderReturnItemResource;
use App\Http\Resources\OrderReturnResource;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use App\Enums\OrderStatus;
use App\Queries\OrderReturn\OrderReturnQuery;
use Illuminate\Http\JsonResponse;

class OrderReturnController extends Controller {
    public function __construct() { $this->authorizeModel(OrderReturn::class, 'orderReturn'); }

    public function index(OrderReturnIndexRequest $request, OrderReturnQuery $query) {
        return OrderReturnResource::collection($query->apply($request->validated(), $request->user())->paginate($request->integer('per_page', 20)));
    }

    public function returnableOrders(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection {
        $user = auth()->user();
        $this->authorize('create', OrderReturn::class);

        $orders = Order::query()->with(['customer', 'items.product', 'invoice'])
            ->where('status', OrderStatus::COMPLETED)
            ->whereHas('items', function ($query) {
                $query->whereRaw('order_items.quantity > (SELECT COALESCE(SUM(order_return_items.quantity), 0) FROM order_return_items INNER JOIN order_returns ON order_returns.id = order_return_items.order_return_id WHERE order_return_items.order_item_id = order_items.id AND order_returns.status NOT IN (?, ?) AND order_returns.deleted_at IS NULL AND order_return_items.deleted_at IS NULL)', ['draft', 'cancelled']);
            });

        // A sales visitor may only see orders registered by that same sales employee.
        // Invoice ownership, delivery responsibility, admin/accountant access, etc.
        // must not affect this rule.
        if ($user?->hasRole('sales visitor')) {
            $employeeId = $user->employee?->id;
            if (!$employeeId) {
                $orders->whereRaw('1 = 0');
            } else {
                $orders->where('sales_employee_id', $employeeId);
            }
        }

        $orders = $orders->orderByDesc('created_at')->limit(100)->get();

        return OrderResource::collection($orders);
    }

    public function returnableOrder(string $order): OrderResource {
        $user = auth()->user();
        $this->authorize('create', OrderReturn::class);

        $order = Order::query()->where('public_id', $order)->firstOrFail();

        if ($user?->hasRole('sales visitor')) {
            $employeeId = $user->employee?->id;
            abort_unless(
                $employeeId
                && $order->sales_employee_id === $employeeId,
                404,
                'این سفارش برای ثبت مرجوعی در دسترس شما نیست.',
            );
        }

        $hasReturnableItems = $order->items()
            ->whereRaw('order_items.quantity > (SELECT COALESCE(SUM(order_return_items.quantity), 0) FROM order_return_items INNER JOIN order_returns ON order_returns.id = order_return_items.order_return_id WHERE order_return_items.order_item_id = order_items.id AND order_returns.status NOT IN (?, ?) AND order_returns.deleted_at IS NULL AND order_return_items.deleted_at IS NULL)', ['draft', 'cancelled'])
            ->exists();

        abort_unless($order->status === OrderStatus::COMPLETED && $hasReturnableItems, 404, 'این سفارش قابل ثبت مرجوعی نیست.');

        $order->load(['customer', 'items.product']);
        return new OrderResource($order);
    }

    public function store(StoreOrderReturnRequest $request, CreateOrderReturnAction $action): JsonResponse {
        $this->authorize('create', OrderReturn::class);
        return response()->json(new OrderReturnResource($action->execute($request->validated(), $request->user())), 201);
    }

    public function show(OrderReturn $orderReturn): OrderReturnResource {
        $this->authorize('view', $orderReturn);
        $orderReturn->load([...OrderReturn::DEFAULT_RELATIONS, 'items.orderItem', 'items.allocations.inventoryBatch']);
        return new OrderReturnResource($orderReturn);
    }

    public function update(UpdateOrderReturnRequest $request, OrderReturn $orderReturn, UpdateOrderReturnAction $action): OrderReturnResource {
        $this->authorize('update', $orderReturn);
        return new OrderReturnResource($action->execute($orderReturn, $request->validated()));
    }

    public function submit(OrderReturn $orderReturn, SubmitOrderReturnAction $action): OrderReturnResource {
        $this->authorize('submit', $orderReturn);
        return new OrderReturnResource($action->execute($orderReturn));
    }

    public function confirm(OrderReturn $orderReturn, ConfirmOrderReturnAction $action): OrderReturnResource {
        $this->authorize('confirm', $orderReturn);
        return new OrderReturnResource($action->execute($orderReturn));
    }

    public function complete(OrderReturn $orderReturn, CompleteOrderReturnAction $action): OrderReturnResource {
        $this->authorize('complete', $orderReturn);
        return new OrderReturnResource($action->execute($orderReturn));
    }

    public function cancel(OrderReturn $orderReturn, CancelOrderReturnAction $action): OrderReturnResource {
        $this->authorize('cancel', $orderReturn);
        return new OrderReturnResource($action->execute($orderReturn));
    }

    public function receive(OrderReturn $orderReturn, ReceiveOrderReturnAction $action): OrderReturnResource {
        $this->authorize('receive', $orderReturn);
        return new OrderReturnResource($action->execute($orderReturn));
    }

    public function allocate(AllocateOrderReturnItemRequest $request, OrderReturnItem $orderReturnItem, AllocateOrderReturnItemAction $action): OrderReturnItemResource {
        $orderReturnItem->loadMissing('orderReturn');
        if (!$orderReturnItem->orderReturn) abort(404, 'مرجوعی مربوط به این آیتم پیدا نشد.');
        $this->authorize('allocate', $orderReturnItem->orderReturn);
        return new OrderReturnItemResource($action->execute($orderReturnItem, $request->validated()['allocations']));
    }
}
