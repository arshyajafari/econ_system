<?php

namespace App\Actions\Delivery;

use App\Enums\DeliveryStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Delivery;
use Illuminate\Support\Facades\DB;

class DeleteDeliveryAction
{
    public function execute(Delivery $delivery): void
    {
        DB::transaction(function () use ($delivery): void {
            $delivery = Delivery::query()
                ->lockForUpdate()
                ->findOrFail($delivery->id);

            if ($delivery->status !== DeliveryStatus::PENDING) {
                throw new BusinessRuleException('فقط ارسال در وضعیت pending قابل حذف است.');
            }

            $delivery->delete();
        });
    }
}
