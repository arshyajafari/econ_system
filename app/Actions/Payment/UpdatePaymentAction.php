<?php

namespace App\Actions\Payment;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdatePaymentAction {
    public function execute(Payment $payment, array $data): Payment {
        return DB::transaction(function () use ($payment, $data) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== PaymentStatus::PENDING) {
                throw new BusinessRuleException('فقط پرداخت در وضعیت pending قابل ویرایش است.');
            }

            $invoice = Invoice::query()->lockForUpdate()
                ->with(['payments', 'returnTransactions.orderReturn'])
                ->findOrFail($payment->invoice_id);

            if ($invoice->status !== InvoiceStatus::ISSUED) {
                throw new BusinessRuleException('فقط فاکتور صادرشده قابل ویرایش پرداخت است.');
            }

            $amount = array_key_exists('amount', $data)
                ? (float) $data['amount']
                : (float) $payment->amount;

            $discountAmount = array_key_exists('settlement_discount_amount', $data)
                ? (float) $data['settlement_discount_amount']
                : (float) $payment->settlement_discount_amount;

            if ($amount <= 0) {
                throw new BusinessRuleException('مبلغ پرداخت باید بیشتر از صفر باشد.');
            }

            if ($discountAmount < 0) {
                throw new BusinessRuleException('مبلغ تخفیف تسویه نمی‌تواند منفی باشد.');
            }

            $confirmedAmount = $invoice->payments
                ->where('status', PaymentStatus::CONFIRMED)
                ->sum(fn ($item) => (float) $item->amount + (float) $item->settlement_discount_amount);

            $otherPendingAmount = $invoice->payments
                ->where('status', PaymentStatus::PENDING)
                ->where('id', '!=', $payment->id)
                ->sum(fn ($item) => (float) $item->amount + (float) $item->settlement_discount_amount);

            $returnCredit = $invoice->completedReturnCreditAmount();

            $remainingAmount = max(
                0,
                (float) $invoice->total_amount
                    - (float) $confirmedAmount
                    - (float) $otherPendingAmount
                    - (float) $returnCredit,
            );

            if ($discountAmount > $remainingAmount) {
                throw new BusinessRuleException('تخفیف تسویه نمی‌تواند بیشتر از مانده فاکتور باشد.');
            }

            if (array_key_exists('method', $data)) {
                $payment->method = $data['method'];
            }

            if (array_key_exists('amount', $data)) {
                $payment->amount = $amount;
            }

            if (array_key_exists('settlement_discount_amount', $data)) {
                $payment->settlement_discount_amount = $discountAmount;
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

            if (array_key_exists('receipt_image', $data) && $data['receipt_image']) {
                $oldPath = $payment->meta['receipt_image_path'] ?? null;
                $newPath = Storage::disk('public')->putFile('payments/receipts', $data['receipt_image']);
                $meta = $payment->meta ?? [];
                $meta['receipt_image_path'] = $newPath;
                $payment->meta = $meta;

                if ($oldPath && $oldPath !== $newPath) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $payment->save();

            return $payment->fresh(Payment::DEFAULT_RELATIONS);
        });
    }
}
