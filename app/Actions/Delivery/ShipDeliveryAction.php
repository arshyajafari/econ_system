<?php

    namespace App\Actions\Delivery;

    use App\Enums\DeliveryStatus;
    use App\Models\Delivery;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class ShipDeliveryAction {
        public function execute(Delivery $delivery): Delivery {
            return DB::transaction(function () use ($delivery) {
                $delivery = Delivery::query()->lockForUpdate()->findOrFail($delivery->id);

                if ($delivery->status !== DeliveryStatus::PREPARING) {
                    throw new RuntimeException('فقط ارسال در وضعیت preparing قابل ارسال است.');
                }

                $delivery->status = DeliveryStatus::SHIPPED;
                $delivery->shipped_at = now();
                $delivery->save();

                return $delivery->fresh(Delivery::DEFAULT_RELATIONS);
            });
        }
    }
