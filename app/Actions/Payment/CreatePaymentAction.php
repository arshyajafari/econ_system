<?php

namespace App\Actions\Payment;

use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Services\CustomerPayableBalanceService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CreatePaymentAction {
    public function __construct(
        protected CustomerPayableBalanceService $payableBalanceService,
    ) {
    }

    public function execute(array $data, User $user): Payment {
        $receiptPath = null;

        try {
            return DB::transaction(function () use ($data, $user, &$receiptPath) {
                $employee = $user->employee;
    
                if (!$employee) {
                    throw new BusinessRuleException('کاربر فعلی به کارمند متصل نیست.');
                }
    
                $customer = Customer::query()
                    ->lockForUpdate()
                    ->where('public_id', $data['customer_id'])
                    ->firstOrFail();
    
                $remainingBalance = $this->payableBalanceService->calculate($customer->id);
    
                if ($remainingBalance <= 0) {
                    throw new BusinessRuleException('این مشتری مانده قابل پرداختی ندارد.');
                }
    
                $amount = (float) $data['amount'];
                $discountAmount = (float) ($data['settlement_discount_amount'] ?? 0);
    
                if ($amount <= 0) {
                    throw new BusinessRuleException('مبلغ پرداخت باید بیشتر از صفر باشد.');
                }
    
                if (Money::sumExceeds($remainingBalance, $amount, $discountAmount)) {
                    throw new BusinessRuleException('مبلغ پرداخت و تخفیف تسویه نمی‌تواند بیشتر از مانده قابل پرداخت مشتری باشد.');
                }
    
                if ($discountAmount < 0) {
                    throw new BusinessRuleException('مبلغ تخفیف تسویه نمی‌تواند منفی باشد.');
                }
    
                if (Money::sumExceeds($remainingBalance, $discountAmount)) {
                    throw new BusinessRuleException('تخفیف تسویه نمی‌تواند بیشتر از مانده حساب مشتری باشد.');
                }
    
                $meta = [];
    
                if (!empty($data['receipt_image'])) {
                    $storedPath = Storage::disk('public')
                        ->putFile('payments/receipts', $data['receipt_image']);
    
                    if (!is_string($storedPath) || $storedPath === '') {
                        throw new BusinessRuleException('ذخیره تصویر رسید پرداخت ناموفق بود.');
                    }
    
                    $receiptPath = $storedPath;
                    $meta['receipt_image_path'] = $storedPath;
                }
    
                $payment = Payment::create([
                    'invoice_id' => null,
                    'customer_id' => $customer->id,
                    'employee_id' => $employee->id,
                    'status' => PaymentStatus::PENDING,
                    'method' => $data['method'],
                    'amount' => $amount,
                    'settlement_discount_amount' => $discountAmount,
                    'reference_number' => $data['reference_number'] ?? null,
                    'payment_date' => $this->normalizePaymentDate($data['payment_date']),
                    'description' => $data['description'] ?? null,
                    'meta' => $meta ?: null,
                ]);
    
                return $payment->fresh(Payment::DEFAULT_RELATIONS);
            });
        } catch (\Throwable $exception) {
            if ($receiptPath !== null) {
                try {
                    Storage::disk('public')->delete($receiptPath);
                } catch (\Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            throw $exception;
        }
    }

    private function normalizePaymentDate(string $paymentDate): Carbon
    {
        $date = Carbon::parse($paymentDate);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($paymentDate)) === 1) {
            $now = now();
            $date->setTime($now->hour, $now->minute, $now->second);
        }

        return $date;
    }
}