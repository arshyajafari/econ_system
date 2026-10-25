<?php

namespace App\Actions\OrderReturn;

use App\Enums\OrderReturnStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\OrderReturnAmountCalculator;
use Illuminate\Support\Facades\DB;

class UpdateOrderReturnAction
{
    public function __construct(protected OrderReturnAmountCalculator $orderReturnAmountCalculator) {}

    public function execute(OrderReturn $orderReturn, array $data): OrderReturn
    {
        return DB::transaction(function () use ($orderReturn, $data) {
            $orderReturn = OrderReturn::query()
                ->lockForUpdate()
                ->with('items')
                ->findOrFail($orderReturn->id);

            if ($orderReturn->status !== OrderReturnStatus::DRAFT) {
                throw new BusinessRuleException('فقط برگشت در وضعیت draft قابل ویرایش است.');
            }

            // Use the order row as the shared mutex with create/confirm return paths.
            // Lock before reading previous returns so the availability check cannot
            // race with another return being created or confirmed for this order.
            $order = Order::query()
                ->lockForUpdate()
                ->with(['items', 'returns.items'])
                ->findOrFail($orderReturn->order_id);

            $submittedItems = collect($data['items'] ?? []);
            if ($submittedItems->isEmpty()) {
                throw new BusinessRuleException('مرجوعی باید حداقل یک قلم داشته باشد.');
            }

            if ($submittedItems->pluck('order_item_id')->unique()->count() !== $submittedItems->count()) {
                throw new BusinessRuleException('هر قلم سفارش را فقط یک بار در فهرست مرجوعی وارد کنید.');
            }

            $requestedQuantities = $submittedItems
                ->groupBy('order_item_id')
                ->map(fn ($items) => [
                    'quantity' => $items->sum(fn ($item) => (int) $item['quantity']),
                    'free_quantity' => $items->sum(fn ($item) => (int) ($item['free_quantity'] ?? 0)),
                ]);

            foreach ($requestedQuantities as $orderItemPublicId => $requested) {
                $orderItem = $order->items->firstWhere('public_id', $orderItemPublicId);

                if (!$orderItem) {
                    throw new BusinessRuleException('آیتم انتخاب‌شده متعلق به این سفارش نیست.');
                }

                $requestedTotal = (int) $requested['quantity'];
                $requestedFree = (int) $requested['free_quantity'];
                $requestedPaid = $requestedTotal - $requestedFree;

                if ($requestedTotal <= 0 || $requestedFree < 0 || $requestedFree > $requestedTotal) {
                    throw new BusinessRuleException('تعداد کل و تعداد رایگان مرجوعی نامعتبر است.');
                }

                $previous = $order->returns
                    ->where('id', '!=', $orderReturn->id)
                    ->reject(fn ($return) => in_array(
                        $return->status,
                        [OrderReturnStatus::DRAFT, OrderReturnStatus::CANCELLED],
                        true,
                    ))
                    ->flatMap(fn ($return) => $return->items)
                    ->where('order_item_id', $orderItem->id);

                $returnedFree = (int) $previous->sum('free_quantity');
                $returnedPaid = (int) $previous->sum('quantity') - $returnedFree;
                $availableFree = (int) $orderItem->effectiveFreeQuantity() - $returnedFree;
                $availablePaid = (int) $orderItem->quantity - $returnedPaid;

                if ($requestedPaid > $availablePaid) {
                    throw new BusinessRuleException('مقدار پولی قابل برگشت برای این آیتم کافی نیست.');
                }

                if ($requestedFree > $availableFree) {
                    throw new BusinessRuleException('مقدار رایگان قابل برگشت برای این آیتم کافی نیست.');
                }
            }

            $manualReturnAmount = isset($data['return_amount'])
                ? round((float) $data['return_amount'], 2)
                : null;

            $meta = $orderReturn->meta ?? [];
            if ($manualReturnAmount !== null) {
                $meta['return_amount'] = $manualReturnAmount;
            } else {
                unset($meta['return_amount']);
            }

            $orderReturn->update([
                'description' => $data['description'] ?? null,
                'meta' => $meta ?: null,
            ]);

            $orderReturn->items()->delete();

            foreach ($submittedItems as $itemData) {
                $orderItem = $order->items->firstWhere('public_id', $itemData['order_item_id']);
                $quantity = (int) $itemData['quantity'];
                $freeQuantity = (int) ($itemData['free_quantity'] ?? 0);
                $paidQuantity = $quantity - $freeQuantity;

                $orderReturn->items()->create([
                    'order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'quantity' => $quantity,
                    'free_quantity' => $freeQuantity,
                    'unit_price' => $orderItem->unit_price,
                    'total_price' => round($paidQuantity * (float) $orderItem->unit_price, 2),
                    'description' => $itemData['description'] ?? null,
                ]);
            }

            if ($manualReturnAmount !== null) {
                $orderReturn->load('items.product.currentPrice');
                $this->orderReturnAmountCalculator->validateManualAmount(
                    $orderReturn->items,
                    $manualReturnAmount
                );
            }

            return $orderReturn->fresh(['order', 'customer', 'employee', 'items.product']);
        });
    }
}
