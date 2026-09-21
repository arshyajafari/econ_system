<?php

namespace App\Actions\Delivery;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateDeliveryAction
{
    public function execute(array $data, User $user): Delivery
    {
        return DB::transaction(function () use ($data, $user) {
            $employee = $user->employee;

            if (!$employee) {
                throw new BusinessRuleException('کاربر فعلی به کارمند متصل نیست.');
            }

            $order = Order::query()
                ->lockForUpdate()
                ->with(['delivery', 'customer.defaultAddress'])
                ->where('public_id', $data['order_id'])
                ->firstOrFail();

            if (!in_array($order->status, [OrderStatus::CONFIRMED, OrderStatus::COMPLETED], true)) {
                throw new BusinessRuleException('فقط سفارش تأییدشده یا تکمیل‌شده قابل ثبت برای ارسال است.');
            }

            if ($order->delivery) {
                throw new BusinessRuleException('برای این سفارش قبلاً ارسال ثبت شده است.');
            }

            $customer = $order->customer;
            $defaultAddress = $customer?->defaultAddress;

            $delivery = Delivery::create([
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'employee_id' => $employee->id,
                'status' => DeliveryStatus::PENDING,
                'recipient_name' => $data['recipient_name'],
                'recipient_phone' => $data['recipient_phone'] ?? $customer?->phone_number,
                'address' => $data['address'] ?? $defaultAddress?->address,
                'description' => $data['description'] ?? null,
                'meta' => array_filter([
                    'province' => $data['province'] ?? null,
                    'city' => $data['city'] ?? null,
                ], static fn ($value) => $value !== null && $value !== ''),
            ]);

            return $delivery->fresh(Delivery::DEFAULT_RELATIONS);
        });
    }
}
