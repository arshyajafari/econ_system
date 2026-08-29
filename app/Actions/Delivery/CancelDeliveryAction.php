<?php

    namespace App\Actions\Delivery;

    use App\Enums\DeliveryStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Delivery;
    use Illuminate\Support\Facades\DB;

    class CancelDeliveryAction {
        public function execute(Delivery $delivery): Delivery {
            return DB::transaction(function () use ($delivery) {
                $delivery = Delivery::query()->lockForUpdate()->findOrFail($delivery->id);

                $cancellableStatuses = [
                    DeliveryStatus::PENDING,
                    DeliveryStatus::PREPARING,
                ];

                if (!in_array($delivery->status, $cancellableStatuses, true)) {
                    throw new BusinessRuleException('این ارسال قابل لغو نیست.');
                }

                $delivery->status = DeliveryStatus::CANCELLED;
                $delivery->cancelled_at = now();
                $delivery->save();

                return $delivery->fresh(Delivery::DEFAULT_RELATIONS);
            });
        }
    }
