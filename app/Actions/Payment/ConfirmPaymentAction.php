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

            $invoice = Invoice::query()->lockForUpdate()->with(['payments', 'returnTransactions.orderReturn'])->findOrFail($payment->invoice_id);

            if ($invoice->status !== InvoiceStatus::ISSUED) {
                throw new BusinessRuleException('فقط فاکتور صادرشده قابل تأیید پرداخت است.');
            }

            if ($payment->customer_id !== $invoice->customer_id) {
                throw new BusinessRuleException('مشتری پرداخت با مشتری فاکتور مطابقت ندارد.');
            }

            $confirmedPaidAmount = $invoice->payments->where('status', PaymentStatus::CONFIRMED)
                ->where('id', '!=', $payment->id)->sum('amount');

            $returnCreditAmount = $invoice->returnTransactions
                ->where('type', 'credit')
                ->filter(fn($transaction) => $transaction->orderReturn?->status?->value === 'completed')
                ->sum('amount');

            $remainingAmount = round(
                (float)$invoice->total_amount - (float)$confirmedPaidAmount - (float)$returnCreditAmount,
                2,
            );

            if ($remainingAmount <= 0) {
                throw new BusinessRuleException('این فاکتور قبلاً با پرداخت‌ها یا اعتبار مرجوعی تسویه شده است.');
            }

            // کل مبلغ پرداخت به حساب مشتری credit می‌شود؛ اگر مبلغ از مانده
            // فاکتور بیشتر باشد، اختلاف به‌صورت بستانکاری مشتری باقی می‌ماند.
            $payment->status = PaymentStatus::CONFIRMED;
            $payment->save();

            $this->customerTransactionService->credit(
                customerId: $payment->customer_id,
                amount: $payment->amount,
                source: $payment,
                description: $payment->description ?? "تأیید پرداخت {$payment->reference_number}",
                transactionAt: $payment->payment_date,
            );

            return $payment->fresh(Payment::DEFAULT_RELATIONS);
        });
    }
}
