<?php

    namespace App\Actions\Invoice;

    use App\Enums\InvoiceStatus;
    use App\Models\Invoice;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class UpdateInvoiceAction {
        public function execute(Invoice $invoice, array $data): Invoice {
            return DB::transaction(function () use ($invoice, $data) {
                $invoice = Invoice::query()->lockForUpdate()->with('items')->findOrFail($invoice->id);

                if ($invoice->status !== InvoiceStatus::DRAFT) {
                    throw new RuntimeException('فقط فاکتور در وضعیت draft قابل ویرایش است.');
                }

                if (array_key_exists('due_date', $data)) {
                    $invoice->due_date = $data['due_date'];
                }

                if (array_key_exists('discount_amount', $data)) {
                    $invoice->discount_amount = $data['discount_amount'];
                }

                if (array_key_exists('tax_amount', $data)) {
                    $invoice->tax_amount = $data['tax_amount'];
                }

                if (array_key_exists('description', $data)) {
                    $invoice->description = $data['description'];
                }

                $invoice->total_amount = (float)$invoice->subtotal - (float)$invoice->discount_amount + (float)$invoice->tax_amount;

                if ($invoice->total_amount < 0) {
                    throw new RuntimeException('مبلغ نهایی فاکتور نمی‌تواند منفی باشد.');
                }

                $invoice->save();

                return $invoice->fresh([
                    ...Invoice::DEFAULT_RELATIONS,
                    'items.orderItem',
                    'items.product',
                ]);
            });
        }
    }
