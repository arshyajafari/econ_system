<?php

namespace App\Actions\Delivery;

use App\Enums\DeliveryStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Delivery;
use Illuminate\Support\Facades\DB;

class UpdateDeliveryAction
{
    public function execute(Delivery $delivery, array $data): Delivery
    {
        return DB::transaction(function () use ($delivery, $data) {
            $delivery = Delivery::query()
                ->lockForUpdate()
                ->with('customer.defaultAddress')
                ->findOrFail($delivery->id);

            if ($delivery->status !== DeliveryStatus::PENDING) {
                throw new BusinessRuleException('فقط ارسال در وضعیت pending قابل ویرایش است.');
            }

            $customer = $delivery->customer;
            $defaultAddress = $customer?->defaultAddress;

            $delivery->update([
                'recipient_name' => $data['recipient_name'],
                'recipient_phone' => $data['recipient_phone'] ?? $customer?->phone_number,
                'address' => $data['address'] ?? $defaultAddress?->address,
                'description' => $data['description'] ?? null,
                'meta' => array_merge($delivery->meta ?? [], array_filter([
                    'province' => $data['province'] ?? null,
                    'city' => $data['city'] ?? null,
                ], static fn ($value) => $value !== null && $value !== '')),
            ]);

            return $delivery->fresh(Delivery::DEFAULT_RELATIONS);
        });
    }
}
