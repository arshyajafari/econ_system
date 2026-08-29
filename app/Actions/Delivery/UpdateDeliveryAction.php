<?php

    namespace App\Actions\Delivery;

    use App\Enums\DeliveryStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Delivery;
    use Illuminate\Support\Facades\DB;

    class UpdateDeliveryAction {
        public function execute(Delivery $delivery, array $data): Delivery {
            return DB::transaction(function () use ($delivery, $data) {
                $delivery = Delivery::query()->lockForUpdate()->findOrFail($delivery->id);

                if ($delivery->status !== DeliveryStatus::PENDING) {
                    throw new BusinessRuleException('فقط ارسال در وضعیت pending قابل ویرایش است.');
                }

                $delivery->update([
                    'recipient_name' => $data['recipient_name'],
                    'recipient_phone' => $data['recipient_phone'],
                    'address' => $data['address'],
                    'description' => $data['description'] ?? null,
                ]);

                return $delivery->fresh(Delivery::DEFAULT_RELATIONS);
            });
        }
    }
