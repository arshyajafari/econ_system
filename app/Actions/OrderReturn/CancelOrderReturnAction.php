<?php

    namespace App\Actions\OrderReturn;

    use App\Enums\OrderReturnStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\OrderReturn;
    use Illuminate\Support\Facades\DB;

    class CancelOrderReturnAction {
        public function execute(OrderReturn $orderReturn): OrderReturn {
            return DB::transaction(function () use ($orderReturn) {
                $orderReturn = OrderReturn::query()->lockForUpdate()->with('items.allocations')
                    ->findOrFail($orderReturn->id);

                $cancellableStatuses = [
                    OrderReturnStatus::DRAFT,
                    OrderReturnStatus::PENDING,
                    OrderReturnStatus::CONFIRMED,
                ];

                if (!in_array($orderReturn->status, $cancellableStatuses, true)) {
                    throw new BusinessRuleException('این برگشت سفارش قابل لغو نیست.');
                }

                foreach ($orderReturn->items as $item) {
                    if ($item->allocations->isNotEmpty()) {
                        $item->allocations()->delete();
                    }
                }

                $orderReturn->status = OrderReturnStatus::CANCELLED;
                $orderReturn->save();

                return $orderReturn->fresh([
                    'order',
                    'customer',
                    'employee',
                    'items.product',
                ]);
            });
        }
    }
