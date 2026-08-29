<?php

    namespace App\Actions\Payment;

    use App\Enums\InvoiceStatus;
    use App\Enums\PaymentStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Invoice;
    use App\Models\Payment;
    use Illuminate\Support\Facades\DB;

    class UpdatePaymentAction {
        public function execute(Payment $payment, array $data): Payment {
            return DB::transaction(function () use ($payment, $data) {
                $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

                if ($payment->status !== PaymentStatus::PENDING) {
                    throw new BusinessRuleException('فقط پرداخت در وضعیت pending قابل ویرایش است.');
                }

                $invoice = Invoice::query()->lockForUpdate()->with('payments')->findOrFail($payment->invoice_id);

                if ($invoice->status !== InvoiceStatus::ISSUED) {
                    throw new BusinessRuleException('فقط فاکتور صادرشده قابل ویرایش پرداخت است.');
                }

                $amount = array_key_exists('amount', $data) ? (float)$data['amount'] : (float)$payment->amount;

                if ($amount <= 0) {
                    throw new BusinessRuleException('مبلغ پرداخت باید بیشتر از صفر باشد.');
                }

                $confirmedPaidAmount = $invoice->payments->where('status', PaymentStatus::CONFIRMED)->sum('amount');

                $otherPendingAmount = $invoice->payments->where('status', PaymentStatus::PENDING)
                    ->where('id', '!=', $payment->id)->sum('amount');

                $remainingAmount = (float)$invoice->total_amount - (float)$confirmedPaidAmount - (float)$otherPendingAmount;

                if ($amount > $remainingAmount) {
                    throw new BusinessRuleException('مبلغ پرداختی بیشتر از مبلغ باقی‌مانده فاکتور است.');
                }

                if (array_key_exists('method', $data)) {
                    $payment->method = $data['method'];
                }

                if (array_key_exists('amount', $data)) {
                    $payment->amount = $amount;
                }

                if (array_key_exists('reference_number', $data)) {
                    $payment->reference_number = $data['reference_number'];
                }

                if (array_key_exists('payment_date', $data)) {
                    $payment->payment_date = $data['payment_date'];
                }

                if (array_key_exists('description', $data)) {
                    $payment->description = $data['description'];
                }

                $payment->save();

                return $payment->fresh(Payment::DEFAULT_RELATIONS);
            });
        }
    }
