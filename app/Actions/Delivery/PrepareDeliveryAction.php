<?php

    namespace App\Actions\Delivery;

    use App\Enums\DeliveryStatus;
    use App\Models\Delivery;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class PrepareDeliveryAction {
        public function execute(Delivery $delivery): Delivery {
            return DB::transaction(function () use ($delivery) {
                $delivery = Delivery::query()->lockForUpdate()->findOrFail($delivery->id);

                if ($delivery->status !== DeliveryStatus::PENDING) {
                    throw new RuntimeException('فقط ارسال در وضعیت pending قابل آماده‌سازی است.');
                }

                $delivery->status = DeliveryStatus::PREPARING;
                $delivery->prepared_at = now();
                $delivery->save();

                return $delivery->fresh(Delivery::DEFAULT_RELATIONS);
            });
        }
    }
