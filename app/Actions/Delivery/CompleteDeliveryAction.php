<?php

    namespace App\Actions\Delivery;

    use App\Enums\DeliveryStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Delivery;
    use Illuminate\Support\Facades\DB;

    class CompleteDeliveryAction {
        public function execute(Delivery $delivery): Delivery {
            return DB::transaction(function () use ($delivery) {
                $delivery = Delivery::query()
                    ->lockForUpdate()
                    ->with('order.invoice')
                    ->findOrFail($delivery->id);

                if ($delivery->status !== DeliveryStatus::SHIPPED) {
                    throw new BusinessRuleException('فقط ارسال در وضعیت shipped قابل تکمیل است.');
                }

                $deliveredAt = now();

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
