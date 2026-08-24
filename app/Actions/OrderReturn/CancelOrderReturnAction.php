<?php

    namespace App\Actions\OrderReturn;

    use App\Enums\OrderReturnStatus;
    use App\Models\OrderReturn;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class CancelOrderReturnAction {
        public function execute(OrderReturn $orderReturn): OrderReturn {
            return DB::transaction(function () use ($orderReturn) {
                $orderReturn = OrderReturn::query()->lockForUpdate()->findOrFail($orderReturn->id);

                $cancellableStatuses = [
                    OrderReturnStatus::DRAFT,
                    OrderReturnStatus::PENDING,
                    OrderReturnStatus::CONFIRMED,
                ];

                if (!in_array($orderReturn->status, $cancellableStatuses, true)) {
                    throw new RuntimeException('این برگشت سفارش قابل لغو نیست.');
                }

                $orderReturn->update([
                    'status' => OrderReturnStatus::CANCELLED,
                ]);

                return $orderReturn->fresh([
                    'order',
                    'customer',
                    'employee',
                    'items.product',
                ]);
            });
        }
    }
