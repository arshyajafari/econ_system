<?php

    namespace App\Actions\OrderReturn;

    use App\Enums\OrderReturnStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\OrderReturn;
    use Illuminate\Support\Facades\DB;

    class UpdateOrderReturnAction {
        public function execute(OrderReturn $orderReturn, array $data): OrderReturn {
            return DB::transaction(function () use (
                $orderReturn, $data
            ) {
                $orderReturn = OrderReturn::query()->lockForUpdate()->with([
                    'items',
                    'order.items',
                    'order.returns.items',
                ])->findOrFail($orderReturn->id);

                if ($orderReturn->status !== OrderReturnStatus::DRAFT) {
                    throw new BusinessRuleException('فقط برگشت در وضعیت draft قابل ویرایش است.');
                }

                $order = $orderReturn->order;

                $orderReturn->update([
                    'description' => $data['description'] ?? null,
                ]);

                $orderReturn->items()->delete();

                foreach ($data['items'] as $itemData) {
                    $orderItem = $order->items->firstWhere('public_id', $itemData['order_item_id']);

                    if (!$orderItem) {
                        throw new BusinessRuleException('آیتم انتخاب‌شده متعلق به این سفارش نیست.');
                    }

                    $alreadyReturned = $order->returns->where('id', '!=', $orderReturn->id)
                        ->reject(fn($return) => $return->status === OrderReturnStatus::DRAFT || $return->status === OrderReturnStatus::CANCELLED)
                        ->flatMap(fn($return) => $return->items)->where('order_item_id', $orderItem->id)
                        ->sum('quantity');

                    $quantity = (int)$itemData['quantity'];

                    if ($quantity <= 0) {
                        throw new BusinessRuleException('مقدار برگشتی باید بیشتر از صفر باشد.');
                    }

                    $returnableQuantity = (int)$orderItem->quantity - (int)$alreadyReturned;

                    if ($quantity > $returnableQuantity) {
                        throw new BusinessRuleException('مقدار برگشتی بیشتر از مقدار قابل برگشت است.');
                    }

                    $orderReturn->items()->create([
                        'order_item_id' => $orderItem->id,
                        'product_id' => $orderItem->product_id,
                        'quantity' => $quantity,
                        'unit_price' => $orderItem->unit_price,
                        'total_price' => $quantity * $orderItem->unit_price,
                        'description' => $itemData['description'] ?? null,
                    ]);
                }

                return $orderReturn->fresh([
                    'order',
                    'customer',
                    'employee',
                    'items.product',
                ]);
            });
        }
    }
