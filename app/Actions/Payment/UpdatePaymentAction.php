<?php

namespace App\Actions\Payment;

use App\Exceptions\BusinessRuleException;
use App\Models\Payment;
use App\Services\CustomerPayableBalanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdatePaymentAction {
    public function __construct(
        protected CustomerPayableBalanceService $payableBalanceService,
    ) {
    }

    public function execute(Payment $payment, array $data): Payment {
        return DB::transaction(function () use ($payment, $data) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status->value !== 'pending') {
                throw new BusinessRuleException('فقط پرداخت در وضعیت pending قابل ویرایش است.');
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

            $remainingBalance = $this->payableBalanceService->calculate(
                customerId: $payment->customer_id,
                excludePaymentId: $payment->id,
            );

            if ($remainingBalance <= 0) {
                throw new BusinessRuleException('این مشتری دیگر مانده قابل پرداختی ندارد.');
            }

            if ($discountAmount > $remainingBalance) {
                throw new BusinessRuleException('تخفیف تسویه نمی‌تواند بیشتر از مانده حساب مشتری باشد.');
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