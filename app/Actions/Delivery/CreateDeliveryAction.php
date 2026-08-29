<?php

    namespace App\Actions\Delivery;

    use App\Enums\DeliveryStatus;
    use App\Enums\OrderStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Delivery;
    use App\Models\Order;
    use App\Models\User;
    use Illuminate\Support\Facades\DB;

    class CreateDeliveryAction {
        public function execute(array $data, User $user): Delivery {
            return DB::transaction(function () use ($data, $user) {
                $employee = $user->employee;

                if (!$employee) {
                    throw new BusinessRuleException('کاربر فعلی به کارمند متصل نیست.');
                }

                $order = Order::query()->lockForUpdate()->with('delivery')->where('public_id', $data['order_id'])
                    ->firstOrFail();

                if ($order->status !== OrderStatus::COMPLETED) {
                    throw new BusinessRuleException('فقط سفارش تکمیل‌شده قابل ثبت برای ارسال است.');
                }

                if ($order->delivery) {
                    throw new BusinessRuleException('برای این سفارش قبلاً ارسال ثبت شده است.');
                }

                $delivery = Delivery::create([
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'employee_id' => null,
                    'status' => DeliveryStatus::PENDING,
                    'recipient_name' => $data['recipient_name'],
                    'recipient_phone' => $data['recipient_phone'],
                    'address' => $data['address'],
                    'description' => $data['description'] ?? null,
                ]);

                return $delivery->fresh(Delivery::DEFAULT_RELATIONS);
            });
        }
    }
