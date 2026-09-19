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

            $paymentAmount = (float) $payment->amount;
            $discountAmount = (float) $payment->settlement_discount_amount;

            /*
             * The current payment is already pending in $invoice->payments.
             * Therefore effectiveRemainingAmount(includePending: true) already
             * subtracts this payment. Adding the current payment back to that
             * value would produce an incorrect remaining amount.
             *
             * Calculate the balance immediately before this payment using:
             *   invoice total
             *   - confirmed payments
             *   - customer credit already allocated to this invoice
             *   - other pending payments
             *
             * Return transactions are deliberately not subtracted here.
             * A return creates customer credit and that credit is allocated
             * explicitly below to this invoice.
             */
            $otherPendingAmount = (float) $invoice->payments
                ->where('status', PaymentStatus::PENDING)
                ->reject(fn ($item) => (int) $item->id === (int) $payment->id)
                ->sum(fn ($item) => (float) $item->amount + (float) $item->settlement_discount_amount);

            $remainingBeforePayment = max(
                0,
                round(
                    (float) $invoice->total_amount
                    - $invoice->settledAmount()
                    - $otherPendingAmount,
                    2,
                ),
            );

            if ($remainingBeforePayment <= 0) {
                throw new BusinessRuleException('این فاکتور قبلاً با پرداخت‌ها یا اعتبار مشتری تسویه شده است.');
            }

            if ($paymentAmount <= 0) {
                throw new BusinessRuleException('مبلغ پرداخت باید بیشتر از صفر باشد.');
            }

            if ($discountAmount < 0) {
                throw new BusinessRuleException('مبلغ تخفیف تسویه نمی‌تواند منفی باشد.');
            }

            if ($paymentAmount + $discountAmount > $remainingBeforePayment) {
                throw new BusinessRuleException('مبلغ پرداخت و تخفیف تسویه بیشتر از مانده فاکتور است.');
            }

            /*
             * Example:
             * invoice = 1000
             * return credit = 200
             * payment = 800
             *
             * creditNeeded = 1000 - 800 = 200
             * -> allocate 200 credit to this invoice
             * -> confirm 800 payment
             * -> settled amount = 200 credit + 800 payment = 1000
             * -> remaining invoice = 0
             */
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
