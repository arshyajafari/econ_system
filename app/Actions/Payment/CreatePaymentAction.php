<?php

namespace App\Actions\Payment;

use App\Enums\DeliveryStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CreatePaymentAction {
    public function execute(array $data, User $user): Payment {
        return DB::transaction(function () use ($data, $user) {
            $employee = $user->employee;

            if (!$employee) {
                throw new BusinessRuleException('کاربر فعلی به کارمند متصل نیست.');
            }

            $invoice = Invoice::query()->lockForUpdate()
                ->with(['payments', 'returnTransactions.orderReturn'])
                ->where('public_id', $data['invoice_id'])
                ->firstOrFail();

            if ($invoice->status !== InvoiceStatus::ISSUED) {
                throw new BusinessRuleException('فقط فاکتور صادرشده قابل پرداخت است.');
            }

            // Payment is allowed only after the delivery has actually been shipped.
            // Lock the delivery row so a concurrent shipment transition cannot
            // race this business-rule check.
            $delivery = Delivery::query()
                ->lockForUpdate()
                ->where('order_id', $invoice->order_id)
                ->first();

            if (!$delivery || !in_array($delivery->status, [DeliveryStatus::SHIPPED, DeliveryStatus::DELIVERED], true)) {
                throw new BusinessRuleException('تا زمانی که ارسال فاکتور انجام نشده باشد، ثبت پرداخت مجاز نیست.');
            }

            $remainingAmount = $invoice->effectiveRemainingAmount(includePending: true);
            $amount = (float) $data['amount'];
            $discountAmount = (float) ($data['settlement_discount_amount'] ?? 0);

            if ($amount <= 0) {
                throw new BusinessRuleException('مبلغ پرداخت باید بیشتر از صفر باشد.');
            }

            if ($discountAmount < 0) {
                throw new BusinessRuleException('مبلغ تخفیف تسویه نمی‌تواند منفی باشد.');
            }

            if ($remainingAmount <= 0) {
                throw new BusinessRuleException('این فاکتور تسویه شده است.');
            }

            if ($discountAmount > $remainingAmount) {
                throw new BusinessRuleException('تخفیف تسویه نمی‌تواند بیشتر از مانده فاکتور باشد.');
            }

            // A customer may intentionally overpay. The invoice is settled
            // up to its remaining balance and the excess becomes reusable
            // customer credit after confirmation.

            $meta = [];

            if (!empty($data['receipt_image'])) {
                $meta['receipt_image_path'] = Storage::disk('public')
                    ->putFile('payments/receipts', $data['receipt_image']);
            }

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'employee_id' => $employee->id,
                'status' => PaymentStatus::PENDING,
                'method' => $data['method'],
                'amount' => $amount,
                'settlement_discount_amount' => $discountAmount,
                'reference_number' => $data['reference_number'] ?? null,
                'payment_date' => $data['payment_date'],
                'description' => $data['description'] ?? null,
                'meta' => $meta ?: null,
            ]);

            return $payment->fresh(Payment::DEFAULT_RELATIONS);
        });
    }
}
