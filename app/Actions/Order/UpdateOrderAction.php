<?php

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class UpdateOrderAction {
    public function __construct(protected SyncOrderItemsAction $syncOrderItems) {
    }

    public function execute(Order $order, array $data): Order {
        return DB::transaction(function () use ($order, $data) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== OrderStatus::DRAFT) {
                throw new BusinessRuleException('فقط سفارش در وضعیت draft قابل ویرایش است.');
            }

            if (isset($data['customer_id'])) {
                $customer = Customer::query()->where('public_id', $data['customer_id'])->firstOrFail();
                $order->customer_id = $customer->id;
            }

            if (isset($data['sales_employee_id'])) {
                $employee = Employee::query()->where('public_id', $data['sales_employee_id'])->firstOrFail();
                $order->sales_employee_id = $employee->id;
            }

            foreach (['description', 'ordered_at', 'discount_type', 'discount_value', 'offer_title', 'offer_description'] as $field) {
                if (array_key_exists($field, $data)) {
                    $order->{$field} = $data[$field];
                }
            }

            if ($order->discount_type === null) {
                $order->discount_type = Order::DISCOUNT_TYPE_NONE;
            }

            if ($order->discount_value === null) {
                $order->discount_value = 0;
            }

            $order->save();

            if (array_key_exists('items', $data)) {
                $this->syncOrderItems->execute($order, $data['items']);
            }

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
