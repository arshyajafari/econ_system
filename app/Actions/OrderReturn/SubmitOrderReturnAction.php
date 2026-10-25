<?php

namespace App\Actions\OrderReturn;

use App\Enums\OrderReturnStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\OrderReturnAmountCalculator;
use Illuminate\Support\Facades\DB;

class SubmitOrderReturnAction
{
    public function __construct(protected OrderReturnAmountCalculator $orderReturnAmountCalculator) {}

    public function execute(OrderReturn $orderReturn): OrderReturn
    {
        return DB::transaction(function () use ($orderReturn) {
            $orderReturn = OrderReturn::query()
                ->lockForUpdate()
                ->with(['items.orderItem', 'items.product.currentPrice'])
                ->findOrFail($orderReturn->id);

            if ($orderReturn->status !== OrderReturnStatus::DRAFT) {
                throw new BusinessRuleException('فقط برگشت در وضعیت draft قابل ارسال است.');
            }

            if ($orderReturn->items->isEmpty()) {
                throw new BusinessRuleException('برگشت سفارش حداقل باید یک آیتم داشته باشد.');
            }

            // Serialize reservation checks with return creation, edits and confirmation.
            $order = Order::query()
                ->lockForUpdate()
                ->with(['items', 'returns.items'])
                ->findOrFail($orderReturn->order_id);

            $manualReturnAmount = data_get($orderReturn->meta, 'return_amount');
            if ($manualReturnAmount !== null) {
                $this->orderReturnAmountCalculator->validateManualAmount(
                    $orderReturn->items,
                    (float) $manualReturnAmount
                );
            }

            foreach ($orderReturn->items as $item) {
                $orderItem = $item->orderItem;

                if (!$orderItem) {
                    throw new BusinessRuleException('آیتم سفارش مربوط به برگشت پیدا نشد.');
                }

                $previousReturns = $order->returns
                    ->where('id', '!=', $orderReturn->id)
                    ->reject(fn ($return) => in_array(
                        $return->status,
                        [OrderReturnStatus::DRAFT, OrderReturnStatus::CANCELLED],
                        true,
                    ))
                    ->flatMap(fn ($return) => $return->items)
                    ->where('order_item_id', $orderItem->id);

                $returnedFree = (int) $previousReturns->sum('free_quantity');
                $returnedPaid = (int) $previousReturns->sum(
                    fn ($previousItem) => (int) $previousItem->quantity - (int) $previousItem->free_quantity,
                );

                $requestedTotal = (int) $item->quantity;
                $requestedFree = (int) $item->free_quantity;

                if ($requestedTotal <= 0 || $requestedFree < 0 || $requestedFree > $requestedTotal) {
                    throw new BusinessRuleException('تعداد کل و تعداد رایگان مرجوعی نامعتبر است.');
                }

                $requestedPaid = $requestedTotal - $requestedFree;
                $remainingReturnable = $orderItem->remainingReturnableQuantities(
                    returnedPaidQuantity: $returnedPaid,
                    returnedFreeQuantity: $returnedFree,
                );

                if ($requestedPaid > $remainingReturnable['paid']) {
                    throw new BusinessRuleException("مقدار پولی برگشتی محصول {$item->product_id} بیشتر از مقدار قابل برگشت است.");
                }

                if ($requestedFree > $remainingReturnable['free']) {
                    throw new BusinessRuleException("مقدار رایگان برگشتی محصول {$item->product_id} بیشتر از مقدار قابل برگشت است.");
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
