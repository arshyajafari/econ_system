<?php

    namespace App\Actions\Payment;

    use App\Enums\InvoiceStatus;
    use App\Enums\PaymentStatus;
    use App\Models\Invoice;
    use App\Models\Payment;
    use App\Services\CustomerTransactionService;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class ConfirmPaymentAction {
        public function __construct(protected CustomerTransactionService $customerTransactionService) {
        }

        public function execute(Payment $payment): Payment {
            return DB::transaction(function () use ($payment) {
                $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

                if ($payment->status !== PaymentStatus::PENDING) {
                    throw new RuntimeException('فقط پرداخت در وضعیت pending قابل تأیید است.');
                }

                $invoice = Invoice::query()->lockForUpdate()->with('payments')->findOrFail($payment->invoice_id);

                if ($invoice->status !== InvoiceStatus::ISSUED) {
                    throw new RuntimeException('فقط فاکتور صادرشده قابل تأیید پرداخت است.');
                }

                if ($payment->customer_id !== $invoice->customer_id) {
                    throw new RuntimeException('مشتری پرداخت با مشتری فاکتور مطابقت ندارد.');
                }

                $confirmedPaidAmount = $invoice->payments->where('status', PaymentStatus::CONFIRMED)
                    ->where('id', '!=', $payment->id)->sum('amount');

                $amount = (float)$payment->amount;

                $remainingAmount = (float)$invoice->total_amount - (float)$confirmedPaidAmount;

                if ($amount > $remainingAmount) {
                    throw new RuntimeException('مبلغ این پرداخت بیشتر از مبلغ باقی‌مانده فاکتور است.');
                }

                $payment->status = PaymentStatus::CONFIRMED;
                $payment->save();

                $this->customerTransactionService->credit(customerId: $payment->customer_id, amount: $payment->amount,
                    source: $payment, description: $payment->description ?? "تأیید پرداخت {$payment->reference_number}",
                    transactionAt: $payment->payment_date);

                return $payment->fresh(Payment::DEFAULT_RELATIONS);
            });
        }
    }
