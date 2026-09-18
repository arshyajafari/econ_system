<?php

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateOrderAction {
    public function __construct(protected SyncOrderItemsAction $syncOrderItems) {
    }

    public function execute(array $data, User $user): Order {
        return DB::transaction(function () use ($data, $user) {
            $customer = Customer::query()->where('public_id', $data['customer_id'])->firstOrFail();

            $salesEmployee = $user->employee;

            if (!$salesEmployee) {
                throw new BusinessRuleException('کاربر واردشده به کارمند متصل نیست و امکان ثبت سفارش وجود ندارد.');
            }

            if (empty($data['items'])) {
                throw new BusinessRuleException('سفارش باید حداقل یک آیتم داشته باشد.');
            }

            $order = Order::create([
                'code' => Order::generateCode(),
                'customer_id' => $customer->id,
                'sales_employee_id' => $salesEmployee->id,
                'status' => OrderStatus::DRAFT,
                'ordered_at' => $data['ordered_at'] ?? null,
                'description' => $data['description'] ?? null,
                'discount_type' => $data['discount_type'] ?? Order::DISCOUNT_TYPE_NONE,
                'discount_value' => $data['discount_value'] ?? 0,
                'offer_title' => $data['offer_title'] ?? null,
                'offer_description' => $data['offer_description'] ?? null,
            ]);

            $this->syncOrderItems->execute($order, $data['items']);

            $this->syncDiscount($order);

            return $order->fresh(Order::DEFAULT_RELATIONS);
        });
    }

    private function syncDiscount(Order $order): void {
        $subtotal = $order->itemsSubtotal();

        if ($order->discount_type === Order::DISCOUNT_TYPE_PERCENTAGE && (float) $order->discount_value > 100) {
            throw new BusinessRuleException('درصد تخفیف نمی‌تواند بیشتر از ۱۰۰ باشد.');
        }

        if ($order->discount_type === Order::DISCOUNT_TYPE_NONE) {
            $order->discount_value = 0;
        }

        if ((float) $order->discount_value > $subtotal) {
            throw new BusinessRuleException('مبلغ تخفیف نمی‌تواند بیشتر از مبلغ کل اقلام سفارش باشد.');
        }

        $order->discount_amount = $order->calculatedDiscountAmount($subtotal);
        $order->save();
    }
}
