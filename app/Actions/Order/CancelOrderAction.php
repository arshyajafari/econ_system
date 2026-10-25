<?php

namespace App\Actions\Order;

use App\Actions\Inventory\ReleaseInventoryReservationAction;
use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CancelOrderAction
{
    public function __construct(protected ReleaseInventoryReservationAction $releaseReservation)
    {
    }

    public function execute(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()->lockForUpdate()->with([
                'items.allocations',
                'delivery',
            ])->findOrFail($order->id);

            if ($order->status === OrderStatus::COMPLETED) {
                throw new BusinessRuleException('سفارش تکمیل‌شده قابل لغو نیست.');
            }

            if ($order->status === OrderStatus::CANCELLED) {
                throw new BusinessRuleException('سفارش قبلاً لغو شده است.');
            }

            if ($order->delivery && $order->delivery->status !== DeliveryStatus::CANCELLED) {
                if (in_array($order->delivery->status, [DeliveryStatus::SHIPPED, DeliveryStatus::DELIVERED], true)) {
                    throw new BusinessRuleException('سفارشی که ارسال آن انجام شده یا به مشتری تحویل شده است قابل لغو نیست.');
                }

                throw new BusinessRuleException('ابتدا ارسال مربوط به این سفارش را لغو کنید.');
            }

            foreach ($order->items as $item) {
                foreach ($item->allocations as $allocation) {
                    $this->releaseReservation->execute($allocation);
                }
            }

            $order->status = OrderStatus::CANCELLED;
            $order->save();

            return $order->fresh(Order::DEFAULT_RELATIONS);
        });
    }
}
