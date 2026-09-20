<?php

namespace App\Actions\OrderReturn;

use App\Enums\OrderReturnStatus;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use App\Services\CodeGeneratorService;
use Illuminate\Support\Facades\DB;

class CreateOrderReturnAction
{
    public function __construct(protected CodeGeneratorService $codeGenerator) {}

    public function execute(array $data, User $user): OrderReturn
    {
        return DB::transaction(function () use ($data, $user) {
            $employee = $user->employee;

            if (!$employee) {
                throw new BusinessRuleException('کاربر فعلی به کارمند متصل نیست.');
            }

            if (empty($data['items'])) {
                throw new BusinessRuleException('مرجوعی باید حداقل یک آیتم داشته باشد.');
            }

            $order = Order::query()
                ->where('public_id', $data['order_id'])
                ->lockForUpdate()
                ->with(['items', 'returns.items'])
                ->firstOrFail();

            if (!$user->hasRole('admin') && $order->sales_employee_id !== $employee->id) {
                throw new BusinessRuleException('فقط سفارش‌های ثبت‌شده توسط خودتان قابل مرجوعی هستند.');
            }

            if ($order->status !== OrderStatus::COMPLETED) {
                throw new BusinessRuleException('فقط سفارش تکمیل‌شده قابل برگشت است.');
            }

            $requestedQuantities = collect($data['items'])
                ->groupBy('order_item_id')
                ->map(fn ($items) => [
                    'quantity' => $items->sum(fn ($item) => (int) $item['quantity']),
                    'free_quantity' => $items->sum(
                        fn ($item) => min((int) $item['quantity'], (int) ($item['free_quantity'] ?? 0)),
                    ),
                ]);

            foreach ($requestedQuantities as $orderItemPublicId => $requested) {
                $orderItem = $order->items->firstWhere('public_id', $orderItemPublicId);

                if (!$orderItem) {
                    throw new BusinessRuleException('آیتم انتخاب‌شده متعلق به این سفارش نیست.');
                }

                $requestedTotal = (int) $requested['quantity'];
                $requestedFree = (int) $requested['free_quantity'];
                $requestedPaid = $requestedTotal - $requestedFree;

                if ($requestedPaid < 0) {
                    throw new BusinessRuleException('تعداد رایگان مرجوعی نمی‌تواند از کل تعداد مرجوعی بیشتر باشد.');
                }

                $previous = $order->returns
                    ->reject(fn ($return) => in_array(
                        $return->status,
                        [OrderReturnStatus::DRAFT, OrderReturnStatus::CANCELLED],
                        true,
                    ))
                    ->flatMap(fn ($return) => $return->items)
                    ->where('order_item_id', $orderItem->id);

                $returnedTotal = $previous->sum('quantity');
                $returnedFree = $previous->sum('free_quantity');
                $returnedPaid = $returnedTotal - $returnedFree;
                $availableFree = $orderItem->effectiveFreeQuantity() - $returnedFree;

                if ($requestedPaid > ((int) $orderItem->quantity - $returnedPaid)) {
                    throw new BusinessRuleException('مقدار پولی قابل برگشت برای این آیتم کافی نیست.');
                }

                if ($requestedFree > $availableFree) {
                    throw new BusinessRuleException('مقدار رایگان قابل برگشت برای این آیتم کافی نیست.');
                }
            }

            $manualReturnAmount = isset($data['return_amount'])
                ? round((float) $data['return_amount'], 2)
                : null;

            $meta = [];
            if ($manualReturnAmount !== null) {
                $meta['return_amount'] = $manualReturnAmount;
            }

            $orderReturn = OrderReturn::create([
                'code' => $this->codeGenerator->generate(OrderReturn::class),
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'employee_id' => $employee->id,
                'status' => OrderReturnStatus::DRAFT,
                'description' => $data['description'] ?? null,
                'meta' => $meta ?: null,
            ]);

            foreach ($data['items'] as $itemData) {
                $orderItem = $order->items->firstWhere('public_id', $itemData['order_item_id']);
                $quantity = (int) $itemData['quantity'];
                $freeQuantity = min($quantity, (int) ($itemData['free_quantity'] ?? 0));
                $paidQuantity = $quantity - $freeQuantity;

                $orderReturn->items()->create([
                    'order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'quantity' => $quantity,
                    'free_quantity' => $freeQuantity,
                    'unit_price' => (float) $orderItem->unit_price,
                    'total_price' => round($paidQuantity * (float) $orderItem->unit_price, 2),
                    'description' => $itemData['description'] ?? null,
                ]);
            }

            return $orderReturn->fresh(['customer', 'employee', 'items.product', 'items.orderItem']);
        });
    }
}
