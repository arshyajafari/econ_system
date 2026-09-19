<?php

namespace App\Actions\Payment;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\CustomerCreditService;
use App\Services\CustomerTransactionService;
use Illuminate\Support\Facades\DB;

class ConfirmPaymentAction {
    public function __construct(
        protected CustomerTransactionService $customerTransactionService,
        protected CustomerCreditService $customerCreditService,
    ) {
    }

    public function execute(Payment $payment): Payment {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== PaymentStatus::PENDING) {
                throw new BusinessRuleException('فقط پرداخت در وضعیت pending قابل تأیید است.');
            }

            $invoice = Invoice::query()->lockForUpdate()
                ->with(['payments', 'creditAllocations', 'returnTransactions.orderReturn'])
                ->findOrFail($payment->invoice_id);

            if ($invoice->status !== InvoiceStatus::ISSUED) {
                throw new BusinessRuleException('فقط فاکتور صادرشده قابل تأیید پرداخت است.');
            }

            if ((int) $payment->customer_id !== (int) $invoice->customer_id) {
                throw new BusinessRuleException('مشتری پرداخت با مشتری فاکتور مطابقت ندارد.');
            }

            $customer = Customer::query()->lockForUpdate()->findOrFail($payment->customer_id);

            $remainingBeforePayment = round(
                $invoice->effectiveRemainingAmount(includePending: true)
                + (float) $payment->amount
                + (float) $payment->settlement_discount_amount,
                2,
            );

            if ($remainingBeforePayment <= 0) {
                throw new BusinessRuleException('این فاکتور قبلاً با پرداخت‌ها یا اعتبار مشتری تسویه شده است.');
            }

            $paymentAmount = (float) $payment->amount;
            $discountAmount = (float) $payment->settlement_discount_amount;

            if ($paymentAmount + $discountAmount > $remainingBeforePayment) {
                throw new BusinessRuleException('مبلغ پرداخت و تخفیف تسویه بیشتر از مانده فاکتور است.');
            }

            $availableCredit = $this->customerCreditService->availableAmount($customer->id);
            $creditNeeded = max(
                0,
                round($remainingBeforePayment - $paymentAmount - $discountAmount, 2),
            );

            if ($creditNeeded > 0 && $availableCredit > 0) {
                $this->customerCreditService->allocateToInvoice(
                    customerId: $customer->id,
                    invoice: $invoice,
                    requestedAmount: min($creditNeeded, $availableCredit),
                    description: "استفاده از اعتبار مشتری برای تسویه فاکتور {$invoice->code}",
                );
            }

            $payment->status = PaymentStatus::CONFIRMED;
            $payment->save();

            $customerCredit = $paymentAmount + $discountAmount;

            $description = $discountAmount > 0
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
