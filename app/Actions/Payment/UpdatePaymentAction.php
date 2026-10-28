<?php

namespace App\Actions\Payment;

use App\Exceptions\BusinessRuleException;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\CustomerPayableBalanceService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdatePaymentAction {
    public function __construct(
        protected CustomerPayableBalanceService $payableBalanceService,
    ) {
    }

    public function execute(Payment $payment, array $data): Payment {
        $newReceiptPath = null;

        try {
            return DB::transaction(function () use ($payment, $data, &$newReceiptPath) {
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
    
                // Serialize balance-affecting payment changes for the same customer.
                // Creation and confirmation already lock this row before calculating
                // the payable balance; without the same lock here, concurrent updates
                // could each validate against a stale balance and over-allocate it.
                Customer::query()->lockForUpdate()->findOrFail($payment->customer_id);
    
                $remainingBalance = $this->payableBalanceService->calculate(
                    customerId: $payment->customer_id,
                    excludePaymentId: $payment->id,
                );
    
                if ($remainingBalance <= 0) {
                    throw new BusinessRuleException('این مشتری دیگر مانده قابل پرداختی ندارد.');
                }
    
                if (Money::sumExceeds($remainingBalance, $amount, $discountAmount)) {
                    throw new BusinessRuleException('مبلغ پرداخت و تخفیف تسویه نمی‌تواند بیشتر از مانده قابل پرداخت مشتری باشد.');
                }
    
                if (Money::sumExceeds($remainingBalance, $discountAmount)) {
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

                $meta = $payment->meta ?? [];
                $chequeFields = [
                    'cheque_due_date' => 'due_date',
                    'cheque_number' => 'number',
                    'cheque_bank_name' => 'bank_name',
                    'cheque_sayad_id' => 'sayad_id',
                ];
                if (($data['method'] ?? $payment->method->value) === 'cheque') {
                    $chequeDetails = $meta['cheque_details'] ?? [
                        'issue_date' => null, 'due_date' => null, 'number' => null,
                        'bank_name' => null, 'sayad_id' => null, 'status' => 'awaiting_clearance',
                    ];
                    // New clients store the cheque number in the canonical reference_number field.
                    if (array_key_exists('reference_number', $data)) {
                        $chequeDetails['number'] = trim((string) ($data['reference_number'] ?? ''));
                    }
                    foreach ($chequeFields as $input => $field) {
                        if (array_key_exists($input, $data)) {
                            $value = $data[$input];
                            $chequeDetails[$field] = in_array($field, ['number', 'bank_name', 'sayad_id'], true)
                                ? (is_string($value) ? trim($value) : $value)
                                : $value;
                        }
                    }
                    // The existing payment date is also the cheque issue date.
                    $chequeDetails['issue_date'] = isset($data['payment_date'])
                        ? $data['payment_date']
                        : $payment->payment_date?->toDateString();
                    $chequeDetails['status'] ??= 'awaiting_clearance';
                    $meta['cheque_details'] = $chequeDetails;
                } else {
                    unset($meta['cheque_details']);
                }
                $payment->meta = $meta ?: null;
    
                if (array_key_exists('payment_date', $data)) {
                    $payment->payment_date = $data['payment_date'];
                }
    
                if (array_key_exists('description', $data)) {
                    $payment->description = $data['description'];
                }
    
                if (array_key_exists('receipt_image', $data) && $data['receipt_image']) {
                    $oldPath = $payment->meta['receipt_image_path'] ?? null;
                    $newPath = Storage::disk('public')->putFile('payments/receipts', $data['receipt_image']);
    
                    if (!is_string($newPath) || $newPath === '') {
                        throw new BusinessRuleException('ذخیره تصویر رسید پرداخت ناموفق بود.');
                    }
    
                    $newReceiptPath = $newPath;
                    $meta = $payment->meta ?? [];
                    $meta['receipt_image_path'] = $newPath;
                    $payment->meta = $meta;
    
                    // Keep the previous receipt intact if the database transaction rolls back.
                    // Cleanup runs only after the new path has been committed successfully.
                    if ($oldPath && $oldPath !== $newPath) {
                        DB::afterCommit(static function () use ($oldPath): void {
                            try {
                                Storage::disk('public')->delete($oldPath);
                            } catch (\Throwable $exception) {
                                report($exception);
                            }
                        });
                    }
                }
    
                $payment->save();
    
                return $payment->fresh(Payment::DEFAULT_RELATIONS);
            });
        } catch (\Throwable $exception) {
            if ($newReceiptPath !== null) {
                try {
                    Storage::disk('public')->delete($newReceiptPath);
                } catch (\Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            throw $exception;
        }
    }
}
