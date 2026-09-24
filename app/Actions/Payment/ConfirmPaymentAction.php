<?php

namespace App\Actions\Payment;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\CustomerCreditService;
use App\Services\CustomerPayableBalanceService;
use App\Services\CustomerTransactionService;
use Illuminate\Support\Facades\DB;

class ConfirmPaymentAction {
    public function __construct(
        protected CustomerTransactionService $customerTransactionService,
        protected CustomerCreditService $customerCreditService,
        protected CustomerPayableBalanceService $payableBalanceService,
    ) {
    }

    public function execute(Payment $payment): Payment {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== PaymentStatus::PENDING) {
                throw new BusinessRuleException('فقط پرداخت در وضعیت pending قابل تأیید است.');
            }

            $customer = Customer::query()->lockForUpdate()->findOrFail($payment->customer_id);

            $remainingBeforePayment = $this->payableBalanceService->calculate(
                customerId: $customer->id,
                excludePaymentId: $payment->id,
            );

            if ($remainingBeforePayment <= 0) {
                throw new BusinessRuleException('این مشتری دیگر مانده قابل پرداختی ندارد.');
            }

            $paymentAmount = (float) $payment->amount;
            $discountAmount = (float) $payment->settlement_discount_amount;

            if ($paymentAmount <= 0) {
                throw new BusinessRuleException('مبلغ پرداخت باید بیشتر از صفر باشد.');
            }

            if ($discountAmount < 0) {
                throw new BusinessRuleException('مبلغ تخفیف تسویه نمی‌تواند منفی باشد.');
            }

            if ($discountAmount > $remainingBeforePayment) {
                throw new BusinessRuleException('تخفیف تسویه نمی‌تواند بیشتر از مانده حساب مشتری باشد.');
            }

            $invoices = Invoice::query()
                ->with(['payments', 'creditAllocations', 'returnTransactions.orderReturn'])
                ->where('customer_id', $customer->id)
                ->where('status', InvoiceStatus::ISSUED)
                ->whereHas('order.delivery', fn ($query) => $query->whereIn('status', ['shipped', 'delivered']))
                ->orderBy('issued_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // Allocate only older payment-originated customer credit first.
            // Unallocated return credit is already reflected in
            // Invoice::effectiveRemainingAmount() and must not be allocated
            // again.
            $availablePaymentCredit = $this->customerCreditService->availablePaymentCreditAmount($customer->id);

            foreach ($invoices as $invoice) {
                if ($availablePaymentCredit <= 0) {
                    break;
                }

                $allocated = $this->customerCreditService->allocatePaymentCreditToInvoice(
                    customerId: $customer->id,
                    invoice: $invoice,
                    requestedAmount: $availablePaymentCredit,
                    description: "استفاده از اعتبار پرداختی مشتری برای فاکتور {$invoice->code}",
                );

                $availablePaymentCredit = max(0, round($availablePaymentCredit - $allocated, 2));
            }

            $payment->status = PaymentStatus::CONFIRMED;
            $payment->save();

            $customerCredit = $paymentAmount + $discountAmount;

            $description = $discountAmount > 0
                ? ($payment->description
                    ? "{$payment->description} - شامل تخفیف تسویه"
                    : "تأیید پرداخت {$payment->reference_number} - شامل تخفیف تسویه")
                : ($payment->description ?? "تأیید پرداخت {$payment->reference_number}");

            $transaction = $this->customerTransactionService->credit(
                customerId: $payment->customer_id,
                amount: $customerCredit,
                source: $payment,
                description: $description,
                transactionAt: $payment->payment_date,
            );

            // Apply the newly confirmed customer-level payment to the oldest
            // eligible invoices. Return credit is already reflected in each
            // invoice's effective remaining amount.
            $remainingPaymentCredit = $customerCredit;

            foreach ($invoices as $invoice) {
                if ($remainingPaymentCredit <= 0) {
                    break;
                }

                $allocated = $this->customerCreditService->allocateSourceToInvoice(
                    sourceTransaction: $transaction,
                    invoice: $invoice,
                    requestedAmount: $remainingPaymentCredit,
                    description: "تخصیص پرداخت {$payment->reference_number} به فاکتور {$invoice->code}",
                );

                $remainingPaymentCredit = max(0, round($remainingPaymentCredit - $allocated, 2));
            }

            return $payment->fresh(Payment::DEFAULT_RELATIONS);
        });
    }
}