<?php

namespace App\Actions\OrderReturn;

use App\Exceptions\BusinessRuleException;
use App\Models\OrderReturn;
use Illuminate\Support\Facades\DB;

class ReceiveOrderReturnAction {
    public function execute(OrderReturn $orderReturn): OrderReturn {
        return DB::transaction(function () use ($orderReturn) {
            $return = OrderReturn::query()->lockForUpdate()->findOrFail($orderReturn->id);
            if ($return->status === \App\Enums\OrderReturnStatus::CANCELLED) {
                throw new BusinessRuleException('مرجوعی لغوشده قابل ثبت به‌عنوان تحویل‌گرفته‌شده نیست.');
            }
            if ($return->delivered_at !== null) {
                return $return->fresh(OrderReturn::DEFAULT_RELATIONS);
            }
            $return->delivered_at = now();
            $return->save();
            return $return->fresh(OrderReturn::DEFAULT_RELATIONS);
        });
    }
}
