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
            $attributes = [];

            if (array_key_exists('recipient_name', $data)) {
                $attributes['recipient_name'] = $data['recipient_name'];
            }

            if (array_key_exists('recipient_phone', $data)) {
                $attributes['recipient_phone'] = $data['recipient_phone']
                    ?? $customer?->phone_number
                    ?? $delivery->recipient_phone;
            }

            if (array_key_exists('address', $data)) {
                $attributes['address'] = $data['address']
                    ?? $defaultAddress?->address
                    ?? $delivery->address;
            }

            if (array_key_exists('description', $data)) {
                $attributes['description'] = $data['description'];
            }

            if (array_key_exists('province', $data) || array_key_exists('city', $data)) {
                $meta = $delivery->meta ?? [];

                foreach (['province', 'city'] as $field) {
                    if (array_key_exists($field, $data)) {
                        if ($data[$field] === null || $data[$field] === '') {
                            unset($meta[$field]);
                        } else {
                            $meta[$field] = $data[$field];
                        }
                    }
                }

                $attributes['meta'] = $meta ?: null;
            }

            if ($attributes !== []) {
                $delivery->update($attributes);
            }

            return $delivery->fresh(Delivery::DEFAULT_RELATIONS);
        });
    }
}
