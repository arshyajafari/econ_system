<?php

namespace App\Actions\Invoice;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\CodeGeneratorService;
use Illuminate\Support\Facades\DB;

class CreateInvoiceAction {
    public function __construct(protected CodeGeneratorService $codeGenerator) {}

    public function execute(Order $order): Invoice {
        return DB::transaction(function () use ($order) {
            $order = Order::query()->lockForUpdate()->with(['customer','items'])->findOrFail($order->id);
            if (!in_array($order->status, [OrderStatus::CONFIRMED, OrderStatus::COMPLETED], true)) throw new BusinessRuleException('فقط سفارش تأییدشده یا تکمیل‌شده قابل ایجاد فاکتور است.');
            if ($order->invoice()->exists()) throw new BusinessRuleException('برای این سفارش قبلاً فاکتور ایجاد شده است.');
            if ($order->items->isEmpty()) throw new BusinessRuleException('سفارش بدون آیتم قابل ایجاد فاکتور نیست.');

            $subtotal = $order->itemsSubtotal();
            $discountAmount = $order->calculatedDiscountAmount($subtotal);
            $totalAmount = round($subtotal - $discountAmount, 2);
            if ($totalAmount <= 0) throw new BusinessRuleException('مبلغ نهایی فاکتور باید بیشتر از صفر باشد.');

            $invoice = Invoice::create([
                'code' => $this->codeGenerator->generate(Invoice::class),
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'employee_id' => $order->sales_employee_id,
                'status' => InvoiceStatus::DRAFT,
                'issued_at' => null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'description' => $order->offer_title ?: null,
                'meta' => [
                    'order_offer_title'=>$order->offer_title,
                    'order_offer_description'=>$order->offer_description,
                    'order_discount_type'=>$order->discount_type,
                    'order_discount_value'=>(float)$order->discount_value,
                ],
            ]);

            foreach ($order->items as $orderItem) {
                $invoice->items()->create([
                    'order_item_id'=>$orderItem->id,
                    'product_id'=>$orderItem->product_id,
                    'quantity'=>$orderItem->quantity,
                    'free_quantity'=>$orderItem->effectiveFreeQuantity(),
                    'unit_price'=>$orderItem->unit_price,
                    'total_price'=>$orderItem->total_price,
                    'description'=>$orderItem->offer_title ?: $orderItem->description,
                ]);
            }

            return $invoice->fresh([...Invoice::DEFAULT_RELATIONS,'items.orderItem','items.product']);
        });
    }
}