<?php

namespace App\Actions\Payment;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\CustomerTransactionService;
use Illuminate\Support\Facades\DB;

class ConfirmPaymentAction {
    public function __construct(protected CustomerTransactionService $customerTransactionService) {
    }

    public function execute(Payment $payment): Payment {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== PaymentStatus::PENDING) {
                throw new BusinessRuleException('فقط پرداخت در وضعیت pending قابل تأیید است.');
            }

            $invoice = Invoice::query()->lockForUpdate()
                ->with(['payments', 'returnTransactions.orderReturn'])
                ->findOrFail($payment->invoice_id);

            if ($invoice->status !== InvoiceStatus::ISSUED) {
                throw new BusinessRuleException('فقط فاکتور صادرشده قابل تأیید پرداخت است.');
            }

            if ((int) $payment->customer_id !== (int) $invoice->customer_id) {
                throw new BusinessRuleException('مشتری پرداخت با مشتری فاکتور مطابقت ندارد.');
            }

            $remainingAmount = $invoice->effectiveRemainingAmount();

            if ($remainingAmount <= 0) {
                throw new BusinessRuleException('این فاکتور قبلاً با پرداخت‌ها یا اعتبار مرجوعی تسویه شده است.');
            }

            if ((float) $payment->settlement_discount_amount > $remainingAmount) {
                throw new BusinessRuleException('تخفیف تسویه بیشتر از مانده فاکتور است.');
            }

            $payment->status = PaymentStatus::CONFIRMED;
            $payment->save();

            $customerCredit = (float) $payment->amount
                + (float) $payment->settlement_discount_amount;

            $description = $payment->settlement_discount_amount > 0
                ? ($payment->description
                    ? "{$payment->description} - شامل تخفیف تسویه"
                    : "تأیید پرداخت {$payment->reference_number} - شامل تخفیف تسویه")
                : ($payment->description ?? "تأیید پرداخت {$payment->reference_number}");

            $this->customerTransactionService->credit(
                customerId: $payment->customer_id,
                amount: $customerCredit,
                source: $payment,
                description: $description,
                transactionAt: $payment->payment_date,
            );

            return $payment->fresh(Payment::DEFAULT_RELATIONS);
        });
    }
}
