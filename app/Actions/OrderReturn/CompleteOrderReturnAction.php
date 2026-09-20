<?php

namespace App\Actions\OrderReturn;

use App\Enums\InventoryMovementType;
use App\Enums\InvoiceStatus;
use App\Enums\OrderReturnStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CustomerTransaction;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\CustomerCreditService;
use App\Services\CustomerTransactionService;
use Illuminate\Support\Facades\DB;

class CompleteOrderReturnAction
{
    public function __construct(
        protected CustomerTransactionService $customerTransactionService,
        protected CustomerCreditService $customerCreditService,
    ) {}

    public function execute(OrderReturn $orderReturn): OrderReturn
    {
        return DB::transaction(function () use ($orderReturn) {
            $orderReturn = OrderReturn::query()->lockForUpdate()->with(['items.allocations.inventoryBatch', 'order.invoice'])->findOrFail($orderReturn->id);
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
            $invoice = Invoice::query()->lockForUpdate()->with(['payments', 'creditAllocations'])->findOrFail($invoice->id);
            if ($invoice->status !== InvoiceStatus::ISSUED) {
                throw new BusinessRuleException('فقط سفارش دارای فاکتور صادرشده قابل تکمیل مرجوعی است.');
            }

            foreach ($orderReturn->items as $item) {
                if ($item->allocations->isEmpty()) {
                    throw new BusinessRuleException('برای تمام اقلام مرجوعی باید تخصیص انبار ثبت شود.');
                }

                $allocatedQuantity = $item->allocations->sum(fn ($allocation) => (int) $allocation->quantity);
                if ($allocatedQuantity !== (int) $item->quantity) {
                    throw new BusinessRuleException('مجموع تخصیص‌های انبار با مقدار مرجوعی برابر نیست.');
                }

                foreach ($item->allocations as $allocation) {
                    $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($allocation->inventory_batch_id);
                    if ((int) $batch->product_id !== (int) $item->product_id) {
                        throw new BusinessRuleException('Batch انتخاب‌شده متعلق به محصول مرجوعی نیست.');
                    }

                    $quantity = (int) $allocation->quantity;
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

            $returnGrossAmount = $orderReturn->items->sum(fn ($item) => (float) $item->total_price);
            $manualReturnAmount = data_get($orderReturn->meta, 'return_amount');

            if ($manualReturnAmount !== null) {
                // Manual amount is an explicit business override, e.g. current-price valuation.
                $returnAmount = round((float) $manualReturnAmount, 2);
                if ($returnAmount <= 0) {
                    throw new BusinessRuleException('مبلغ دستی مرجوعی باید بیشتر از صفر باشد.');
                }
            } else {
                $invoiceSubtotal = (float) $invoice->subtotal;
                $invoiceTotal = (float) $invoice->total_amount;

                if ($invoiceSubtotal <= 0 || $invoiceTotal <= 0) {
                    throw new BusinessRuleException('مبالغ فاکتور برای محاسبه مرجوعی معتبر نیستند.');
                }

                $calculatedReturnAmount = round(($returnGrossAmount / $invoiceSubtotal) * $invoiceTotal, 2);

                $previousReturnCredit = (float) CustomerTransaction::query()
                    ->where('customer_id', $orderReturn->customer_id)
                    ->whereHas('orderReturn', function ($query) use ($orderReturn) {
                        $query->where('order_id', $orderReturn->order_id)
                            ->where('status', OrderReturnStatus::COMPLETED)
                            ->whereKeyNot($orderReturn->id);
                    })
                    ->where('type', 'credit')
                    ->sum('amount');

                $remainingReturnCredit = round($invoiceTotal - $previousReturnCredit, 2);
                if ($remainingReturnCredit <= 0) {
                    throw new BusinessRuleException('مبلغ قابل اعتبار برای این مرجوعی باقی نمانده است.');
                }

                $returnAmount = min($calculatedReturnAmount, $remainingReturnCredit);
            }

            if ($returnAmount <= 0) {
                throw new BusinessRuleException('مبلغ اعتبار مرجوعی باید بیشتر از صفر باشد.');
            }

            $completedAt = now();

            $creditTransaction = $this->customerTransactionService->credit(
                customerId: $orderReturn->customer_id,
                amount: $returnAmount,
                source: $orderReturn,
                description: "مرجوعی سفارش {$orderReturn->code}",
                transactionAt: $completedAt,
            );

            // Apply the return credit to the originating invoice first.
            // Any amount above the invoice's current receivable remains customer credit.
            $invoiceRemainingBeforeReturn = $invoice->effectiveRemainingAmount();

            if ($invoiceRemainingBeforeReturn > 0) {
                $this->customerCreditService->allocateSourceToInvoice(
                    sourceTransaction: $creditTransaction,
                    invoice: $invoice,
                    requestedAmount: min($returnAmount, $invoiceRemainingBeforeReturn),
                    description: "کسر مبلغ مرجوعی {$orderReturn->code} از فاکتور {$invoice->code}",
                );
            }

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
