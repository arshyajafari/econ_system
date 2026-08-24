<?php

    namespace App\Actions\Invoice;

    use App\Enums\InvoiceStatus;
    use App\Enums\OrderStatus;
    use App\Models\Invoice;
    use App\Models\Order;
    use App\Services\CodeGeneratorService;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class CreateInvoiceAction {
        public function __construct(protected CodeGeneratorService $codeGenerator) {
        }

        public function execute(Order $order): Invoice {
            return DB::transaction(function () use ($order) {
                $order = Order::query()->lockForUpdate()->with([
                        'customer',
                        'items',
                    ])->findOrFail($order->id);

                if ($order->status !== OrderStatus::COMPLETED) {
                    throw new RuntimeException('فقط سفارش تکمیل‌شده قابل ایجاد فاکتور است.');
                }

                if ($order->invoice()->exists()) {
                    throw new RuntimeException('برای این سفارش قبلاً فاکتور ایجاد شده است.');
                }

                if ($order->items->isEmpty()) {
                    throw new RuntimeException('سفارش بدون آیتم قابل ایجاد فاکتور نیست.');
                }

                $subtotal = $order->items->sum(fn($item) => (float)$item->total_price);

                $code = $this->codeGenerator->generate(Invoice::class);

                $invoice = Invoice::create([
                    'code' => $code,
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'employee_id' => $order->sales_employee_id,
                    'status' => InvoiceStatus::DRAFT,
                    'issued_at' => null,
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'total_amount' => $subtotal,
                    'description' => null,
                ]);

                foreach ($order->items as $orderItem) {
                    $invoice->items()->create([
                        'order_item_id' => $orderItem->id,
                        'product_id' => $orderItem->product_id,
                        'quantity' => $orderItem->quantity,
                        'unit_price' => $orderItem->unit_price,
                        'total_price' => $orderItem->total_price,
                        'description' => $orderItem->description,
                    ]);
                }

                return $invoice->fresh([
                    ...Invoice::DEFAULT_RELATIONS,
                    'items.orderItem',
                    'items.product',
                ]);
            });
        }
    }
