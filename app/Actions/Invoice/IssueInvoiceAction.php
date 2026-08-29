<?php

    namespace App\Actions\Invoice;

    use App\Enums\InvoiceStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Invoice;
    use App\Services\CustomerTransactionService;
    use Illuminate\Support\Facades\DB;

    class IssueInvoiceAction {
        public function __construct(protected CustomerTransactionService $customerTransactionService) {
        }

        public function execute(Invoice $invoice): Invoice {
            return DB::transaction(function () use ($invoice) {
                $invoice = Invoice::query()->lockForUpdate()->with('items')->findOrFail($invoice->id);

                if ($invoice->status !== InvoiceStatus::DRAFT) {
                    throw new BusinessRuleException('فقط فاکتور در وضعیت draft قابل صدور است.');
                }

                if ($invoice->items->isEmpty()) {
                    throw new BusinessRuleException('فاکتور باید حداقل یک آیتم داشته باشد.');
                }

                $subtotal = $invoice->items->sum(fn($item) => (float)$item->total_price);

                $expectedTotal = $subtotal - (float)$invoice->discount_amount + (float)$invoice->tax_amount;

                if ($expectedTotal <= 0) {
                    throw new BusinessRuleException('مبلغ نهایی فاکتور باید بیشتر از صفر باشد.');
                }

                if (round((float)$invoice->total_amount, 2) !== round($expectedTotal, 2)) {
                    throw new BusinessRuleException('مبلغ نهایی فاکتور با اقلام فاکتور مطابقت ندارد.');
                }

                $issuedAt = now();

                $invoice->status = InvoiceStatus::ISSUED;
                $invoice->issued_at = $issuedAt;
                $invoice->save();

                $this->customerTransactionService->debit(customerId: $invoice->customer_id,
                    amount: $invoice->total_amount, source: $invoice, description: "صدور فاکتور {$invoice->code}",
                    transactionAt: $issuedAt);

                return $invoice->fresh([
                    ...Invoice::DEFAULT_RELATIONS,
                    'items.orderItem',
                    'items.product',
                ]);
            });
        }
    }
