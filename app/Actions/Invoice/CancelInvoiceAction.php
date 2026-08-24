<?php

    namespace App\Actions\Invoice;

    use App\Enums\InvoiceStatus;
    use App\Models\Invoice;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class CancelInvoiceAction {
        public function execute(Invoice $invoice): Invoice {
            return DB::transaction(function () use ($invoice) {
                $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

                if ($invoice->status === InvoiceStatus::CANCELLED) {
                    throw new RuntimeException('فاکتور قبلاً لغو شده است.');
                }

                if ($invoice->status !== InvoiceStatus::DRAFT) {
                    throw new RuntimeException('فقط فاکتور در وضعیت draft قابل لغو است.');
                }

                $invoice->update([
                    'status' => InvoiceStatus::CANCELLED,
                ]);

                return $invoice->fresh(Invoice::DEFAULT_RELATIONS);
            });
        }
    }
