<?php

    namespace App\Actions\Payment;

    use App\Enums\PaymentStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Payment;
    use Illuminate\Support\Facades\DB;

    class CancelPaymentAction {
        public function execute(Payment $payment): Payment {
            return DB::transaction(function () use ($payment) {
                $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

                if ($payment->status === PaymentStatus::CANCELLED) {
                    throw new BusinessRuleException('این پرداخت قبلاً لغو شده است.');
                }

                if ($payment->status === PaymentStatus::CONFIRMED) {
                    throw new BusinessRuleException('پرداخت تأییدشده قابل لغو نیست.');
                }

                if ($payment->status !== PaymentStatus::PENDING) {
                    throw new BusinessRuleException('فقط پرداخت در وضعیت pending قابل لغو است.');
                }

                $payment->status = PaymentStatus::CANCELLED;
                $payment->save();

                return $payment->fresh(Payment::DEFAULT_RELATIONS);
            });
        }
    }
