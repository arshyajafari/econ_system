<?php

    namespace App\Actions\OrderReturn;

    use App\Enums\OrderReturnStatus;
    use App\Models\OrderReturn;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class SubmitOrderReturnAction {
        public function execute(OrderReturn $orderReturn): OrderReturn {
            return DB::transaction(function () use ($orderReturn) {
                $orderReturn = OrderReturn::query()->lockForUpdate()->with([
                        'items.orderItem',
                        'order.items',
                        'order.returns.items',
                    ])->findOrFail($orderReturn->id);

                if ($orderReturn->status !== OrderReturnStatus::DRAFT) {
                    throw new RuntimeException('فقط برگشت در وضعیت draft قابل ارسال است.');
                }

                if ($orderReturn->items->isEmpty()) {
                    throw new RuntimeException('برگشت سفارش حداقل باید یک آیتم داشته باشد.');
                }

                $order = $orderReturn->order;

                if (!$order) {
                    throw new RuntimeException('سفارش مربوط به برگشت پیدا نشد.');
                }

                foreach ($orderReturn->items as $item) {
                    $orderItem = $item->orderItem;

                    if (!$orderItem) {
                        throw new RuntimeException('آیتم سفارش مربوط به برگشت پیدا نشد.');
                    }

                    $alreadyReturned = $order->returns->where('id', '!=', $orderReturn->id)
                        ->reject(fn($return) => $return->status === OrderReturnStatus::DRAFT || $return->status === OrderReturnStatus::CANCELLED)
                        ->flatMap(fn($return) => $return->items)->where('order_item_id', $orderItem->id)
                        ->sum('quantity');

                    $returnableQuantity = (int)$orderItem->quantity - (int)$alreadyReturned;

                    if ((int)$item->quantity > $returnableQuantity) {
                        throw new RuntimeException("مقدار برگشتی محصول {$item->product_id} بیشتر از مقدار قابل برگشت است.");
                    }
                }

                $orderReturn->status = OrderReturnStatus::PENDING;
                $orderReturn->save();

                return $orderReturn->fresh([
                    'order',
                    'customer',
                    'employee',
                    'items.product',
                ]);
            });
        }
    }
