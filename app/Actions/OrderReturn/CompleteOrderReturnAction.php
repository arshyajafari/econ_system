<?php

    namespace App\Actions\OrderReturn;

    use App\Enums\InventoryMovementType;
    use App\Enums\InvoiceStatus;
    use App\Enums\OrderReturnStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\CustomerTransaction;
    use App\Models\InventoryBatch;
    use App\Models\InventoryMovement;
    use App\Models\Order;
    use App\Models\OrderReturn;
    use App\Services\CustomerTransactionService;
    use Illuminate\Support\Facades\DB;

    class CompleteOrderReturnAction {
        public function __construct(protected CustomerTransactionService $customerTransactionService) {
        }

        public function execute(OrderReturn $orderReturn): OrderReturn {
            return DB::transaction(function () use ($orderReturn) {
                $orderReturn = OrderReturn::query()->lockForUpdate()->with([
                    'items.allocations.inventoryBatch',
                    'order.invoice',
                ])->findOrFail($orderReturn->id);

                if ($orderReturn->status !== OrderReturnStatus::CONFIRMED) {
                    throw new BusinessRuleException('فقط مرجوعی در وضعیت confirmed قابل تکمیل است.');
                }

                if ($orderReturn->items->isEmpty()) {
                    throw new BusinessRuleException('مرجوعی باید حداقل یک آیتم داشته باشد.');
                }

                $order = Order::query()->lockForUpdate()->with('invoice')->findOrFail($orderReturn->order_id);

                $invoice = $order->invoice;

                if (!$invoice) {
                    throw new BusinessRuleException('برای سفارش مربوط به مرجوعی فاکتور وجود ندارد.');
                }

                if ($invoice->status !== InvoiceStatus::ISSUED) {
                    throw new BusinessRuleException('فقط سفارش دارای فاکتور صادرشده قابل تکمیل مرجوعی است.');
                }

                foreach ($orderReturn->items as $item) {
                    if ($item->allocations->isEmpty()) {
                        throw new BusinessRuleException('برای تمام اقلام مرجوعی باید تخصیص انبار ثبت شود.');
                    }

                    $allocatedQuantity = $item->allocations->sum(fn($allocation) => (int)$allocation->quantity);

                    if ((int)$allocatedQuantity !== (int)$item->quantity) {
                        throw new BusinessRuleException('مجموع تخصیص‌های انبار با مقدار مرجوعی برابر نیست.');
                    }

                    foreach ($item->allocations as $allocation) {
                        $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($allocation->inventory_batch_id);

                        if ((int)$batch->product_id !== (int)$item->product_id) {
                            throw new BusinessRuleException('Batch انتخاب‌شده متعلق به محصول مرجوعی نیست.');
                        }

                        $quantity = (int)$allocation->quantity;

                        if ($quantity <= 0) {
                            throw new BusinessRuleException('مقدار تخصیص انبار باید بیشتر از صفر باشد.');
                        }

                        $batch->quantity += $quantity;
                        $batch->save();

                        InventoryMovement::create([
                            'inventory_batch_id' => $batch->id,
                            'type' => InventoryMovementType::IN,
                            'quantity' => $quantity,
                            'reason' => 'order_return',
                            'description' => $orderReturn->description,
                            'moved_at' => now(),
                        ]);
                    }
                }

                $returnAmount = $orderReturn->items->sum(fn($item) => (float)$item->total_price);

                if ($returnAmount <= 0) {
                    throw new BusinessRuleException('مبلغ مرجوعی باید بیشتر از صفر باشد.');
                }

                $invoiceSubtotal = (float)$invoice->subtotal;
                $invoiceTotal = (float)$invoice->total_amount;

                if ($invoiceSubtotal <= 0) {
                    throw new BusinessRuleException('مبلغ پایه فاکتور برای محاسبه مرجوعی معتبر نیست.');
                }

                if ($invoiceTotal <= 0) {
                    throw new BusinessRuleException('مبلغ نهایی فاکتور برای محاسبه مرجوعی معتبر نیست.');
                }

                $calculatedReturnAmount = round(($returnAmount / $invoiceSubtotal) * $invoiceTotal, 2);

                if ($calculatedReturnAmount <= 0) {
                    throw new BusinessRuleException('مبلغ اعتبار مرجوعی باید بیشتر از صفر باشد.');
                }

                $previousReturnCredit = (float)CustomerTransaction::query()
                    ->where('customer_id', $orderReturn->customer_id)
                    ->whereHas('orderReturn', function ($query) use ($orderReturn) {
                        $query->where('order_id', $orderReturn->order_id)->where('status', OrderReturnStatus::COMPLETED)
                            ->whereKeyNot($orderReturn->id);
                    })->where('type', 'credit')->sum('amount');

                $remainingReturnCredit = round($invoiceTotal - $previousReturnCredit, 2);

                if ($remainingReturnCredit <= 0) {
                    throw new BusinessRuleException('مبلغ قابل اعتبار برای این مرجوعی باقی نمانده است.');
                }

                $returnAmount = min($calculatedReturnAmount, $remainingReturnCredit);

                if ($returnAmount <= 0) {
                    throw new BusinessRuleException('مبلغ اعتبار مرجوعی باید بیشتر از صفر باشد.');
                }

                $completedAt = now();

                $this->customerTransactionService->credit(customerId: $orderReturn->customer_id, amount: $returnAmount,
                    source: $orderReturn, description: "مرجوعی سفارش {$orderReturn->code}",
                    transactionAt: $completedAt);

                $orderReturn->status = OrderReturnStatus::COMPLETED;
                $orderReturn->completed_at = $completedAt;
                $orderReturn->save();

                return $orderReturn->fresh([
                    'order',
                    'customer',
                    'employee',
                    'items.product',
                    'items.orderItem',
                    'items.allocations.inventoryBatch.product',
                ]);
            });
        }
    }
