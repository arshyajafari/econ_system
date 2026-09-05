<?php

    namespace App\Actions\OrderReturn;

    use App\Enums\OrderReturnStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\InventoryBatch;
    use App\Models\OrderReturnItem;
    use App\Models\OrderReturnItemAllocation;
    use Illuminate\Support\Facades\DB;

    class AllocateOrderReturnItemAction {
        public function execute(OrderReturnItem $orderReturnItem, array $allocations): OrderReturnItem {
            return DB::transaction(function () use (
                $orderReturnItem, $allocations,
            ) {
                $orderReturnItem = OrderReturnItem::query()->lockForUpdate()->with('orderReturn')
                    ->findOrFail($orderReturnItem->id);

                if (!$orderReturnItem->orderReturn) {
                    throw new BusinessRuleException('مرجوعی مربوط به این آیتم پیدا نشد.');
                }

                if ($orderReturnItem->orderReturn->status !== OrderReturnStatus::CONFIRMED) {
                    throw new BusinessRuleException('فقط مرجوعی در وضعیت confirmed قابل تخصیص انبار است.');
                }

                if (empty($allocations)) {
                    throw new BusinessRuleException('حداقل یک تخصیص انبار باید ثبت شود.');
                }

                $existingAllocatedQuantity = OrderReturnItemAllocation::query()
                    ->where('order_return_item_id', $orderReturnItem->id)->sum('quantity');

                if ($existingAllocatedQuantity > 0) {
                    throw new BusinessRuleException('برای این قلم مرجوعی قبلاً تخصیص انبار ثبت شده است.');
                }

                $requestedAllocations = collect($allocations)->groupBy('inventory_batch_id')
                    ->map(fn($items) => $items->sum(fn($item) => (int)$item['quantity']));

                $allocatedQuantity = 0;
                $batches = [];

                foreach ($requestedAllocations as $inventoryBatchPublicId => $quantity) {
                    $quantity = (int)$quantity;

                    if ($quantity <= 0) {
                        throw new BusinessRuleException('مقدار تخصیص باید بیشتر از صفر باشد.');
                    }

                    $batch = InventoryBatch::query()->lockForUpdate()->where('public_id', $inventoryBatchPublicId)
                        ->firstOrFail();

                    if ((int)$batch->product_id !== (int)$orderReturnItem->product_id) {
                        throw new BusinessRuleException('Batch انتخاب‌شده متعلق به محصول این قلم مرجوعی نیست.');
                    }

                    $allocatedQuantity += $quantity;

                    $batches[] = [
                        'batch' => $batch,
                        'quantity' => $quantity,
                    ];
                }

                if ($allocatedQuantity !== (int)$orderReturnItem->quantity) {
                    throw new BusinessRuleException('مجموع تخصیص‌ها باید برابر با مقدار کالای مرجوعی باشد.');
                }

                foreach ($batches as $allocationData) {
                    OrderReturnItemAllocation::create([
                        'order_return_item_id' => $orderReturnItem->id,
                        'inventory_batch_id' => $allocationData['batch']->id,
                        'quantity' => $allocationData['quantity'],
                    ]);
                }

                return $orderReturnItem->fresh([
                    'orderReturn',
                    'product',
                    'allocations.inventoryBatch.product',
                ]);
            });
        }
    }
