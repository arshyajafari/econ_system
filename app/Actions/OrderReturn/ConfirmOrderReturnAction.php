<?php

    namespace App\Actions\OrderReturn;

    use App\Enums\OrderReturnStatus;
    use App\Enums\OrderStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Order;
    use App\Models\OrderReturn;
    use Illuminate\Support\Facades\DB;

    class ConfirmOrderReturnAction {
        public function execute(OrderReturn $orderReturn): OrderReturn {
            return DB::transaction(function () use ($orderReturn) {
                $orderReturn = OrderReturn::query()->lockForUpdate()->with([
                    'items.orderItem',
                    'customer',
                    'employee',
                ])->findOrFail($orderReturn->id);

                if ($orderReturn->status !== OrderReturnStatus::PENDING) {
                    throw new BusinessRuleException('فقط برگشت در وضعیت pending قابل تأیید است.');
                }

                if ($orderReturn->items->isEmpty()) {
                    throw new BusinessRuleException('برگشت سفارش حداقل باید یک آیتم داشته باشد.');
                }

                $order = Order::query()->lockForUpdate()->with([
                    'items',
                    'returns.items',
                ])->findOrFail($orderReturn->order_id);

                if ($order->status !== OrderStatus::COMPLETED) {
                    throw new BusinessRuleException('فقط سفارش تکمیل‌شده قابل تأیید مرجوعی است.');
                }

                foreach ($orderReturn->items as $item) {
                    $orderItem = $item->orderItem;

                    if (!$orderItem) {
                        throw new BusinessRuleException('آیتم سفارش مربوط به برگشت پیدا نشد.');
                    }

                    $alreadyReturned = $order->returns->where('id', '!=', $orderReturn->id)
                        ->reject(fn($return) => $return->status === OrderReturnStatus::DRAFT || $return->status === OrderReturnStatus::CANCELLED)
                        ->flatMap(fn($return) => $return->items)->where('order_item_id', $orderItem->id)
                        ->sum('quantity');

                    $returnableQuantity = (int)$orderItem->quantity - (int)$alreadyReturned;

                    if ((int)$item->quantity > $returnableQuantity) {
                        throw new BusinessRuleException('مقدار برگشتی بیشتر از مقدار قابل برگشت است.');
                    }
                }

                $orderReturn->status = OrderReturnStatus::CONFIRMED;
                $orderReturn->save();

                return $orderReturn->fresh([
                    'order',
                    'customer',
                    'employee',
                    'items.product',
                    'items.orderItem',
                ]);
            });
        }
    }
