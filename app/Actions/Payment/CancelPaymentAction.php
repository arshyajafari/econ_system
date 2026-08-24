<?php

    namespace App\Actions\Payment;

    use App\Enums\PaymentStatus;
    use App\Models\Payment;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class CancelPaymentAction {
        public function execute(Payment $payment): Payment {
            return DB::transaction(function () use ($payment) {
                $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

                if ($payment->status === PaymentStatus::CANCELLED) {
                    throw new RuntimeException('این پرداخت قبلاً لغو شده است.');
                }

                if ($payment->status === PaymentStatus::CONFIRMED) {
                    throw new RuntimeException('پرداخت تأییدشده قابل لغو نیست.');
                }

                if ($payment->status !== PaymentStatus::PENDING) {
                    throw new RuntimeException('فقط پرداخت در وضعیت pending قابل لغو است.');
                }

                $payment->status = PaymentStatus::CANCELLED;
                $payment->save();

                return $payment->fresh(Payment::DEFAULT_RELATIONS);
            });
        }
    }
