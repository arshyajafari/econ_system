<?php

    namespace App\Actions\Invoice;

    use App\Models\Invoice;

    class ShowInvoiceAction {
        public function execute(Invoice $invoice): Invoice {
            return $invoice->fresh([
                ...Invoice::DEFAULT_RELATIONS,
                'items.orderItem',
                'items.product',
                'payments',
            ]);
        }
    }
