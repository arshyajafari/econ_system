<?php

    namespace App\Actions\Delivery;

    use App\Enums\DeliveryStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Delivery;
    use App\Models\User;
    use Illuminate\Support\Facades\DB;

    class CompleteDeliveryAction {
        public function execute(Delivery $delivery, array $data = [], ?User $user = null): Delivery {
            return DB::transaction(function () use ($delivery, $data, $user) {
                $delivery = Delivery::query()
                    ->lockForUpdate()
                    ->with(['order.invoice', 'customer'])
                    ->findOrFail($delivery->id);

                if ($delivery->status !== DeliveryStatus::SHIPPED) {
                    throw new BusinessRuleException('فقط ارسال در وضعیت shipped قابل تکمیل است.');
                }

                $recipientName = trim((string) ($data['recipient_name'] ?? ''));
                if ($recipientName === '') {
                    throw new BusinessRuleException('نام گیرنده برای ثبت تحویل الزامی است.');
                }

                $recipientPhone = trim((string) ($data['recipient_phone'] ?? ''));
                if ($recipientPhone === '') {
                    $recipientPhone = trim((string) ($delivery->customer?->phone_number ?? ''));
                }
                if ($recipientPhone === '') {
                    throw new BusinessRuleException('شماره گیرنده وارد نشده و شماره‌ای هم برای مشتری ثبت نشده است.');
                }

                $employee = $user?->employee;
                if (!$employee) {
                    throw new BusinessRuleException('کاربر مسئول تحویل به پرونده کارمند متصل نیست.');
                }

                $deliveredAt = now();
                $delivery->employee_id = $employee->id;
                $delivery->recipient_name = $recipientName;
                $delivery->recipient_phone = $recipientPhone;
                if (array_key_exists('description', $data)) {
                    $delivery->description = $data['description'] ?: null;
                }

                $delivery->status = DeliveryStatus::DELIVERED;
                $delivery->delivered_at = $deliveredAt;
                $delivery->save();

                if ($delivery->order?->invoice) {
                    $delivery->order->invoice->due_date = $deliveredAt
                        ->copy()
                        ->addMonthsNoOverflow(4)
                        ->toDateString();
                    $delivery->order->invoice->save();
                }

                return $delivery->fresh(Delivery::DEFAULT_RELATIONS);
            });
        }
    }
