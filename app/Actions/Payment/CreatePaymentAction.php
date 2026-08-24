<?php

    namespace App\Actions\Payment;

    use App\Enums\InvoiceStatus;
    use App\Enums\PaymentStatus;
    use App\Models\Invoice;
    use App\Models\Payment;
    use App\Models\User;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class CreatePaymentAction {
        public function execute(array $data, User $user): Payment {
            return DB::transaction(function () use ($data, $user) {
                $employee = $user->employee;

                if (!$employee) {
                    throw new RuntimeException('کاربر فعلی به کارمند متصل نیست.');
                }

                $invoice = Invoice::query()->lockForUpdate()->with('payments')->where('public_id', $data['invoice_id'])
                    ->firstOrFail();

                if ($invoice->status !== InvoiceStatus::ISSUED) {
                    throw new RuntimeException('فقط فاکتور صادرشده قابل پرداخت است.');
                }

                $confirmedPaidAmount = $invoice->payments->where('status', PaymentStatus::CONFIRMED)->sum('amount');

                $pendingPaidAmount = $invoice->payments->where('status', PaymentStatus::PENDING)->sum('amount');

                $remainingAmount = (float)$invoice->total_amount - (float)$confirmedPaidAmount - (float)$pendingPaidAmount;

                $amount = (float)$data['amount'];

                if ($remainingAmount <= 0) {
                    throw new RuntimeException('این فاکتور تسویه شده است.');
                }

                if ($amount > $remainingAmount) {
                    throw new RuntimeException('مبلغ پرداختی بیشتر از مبلغ باقی‌مانده فاکتور است.');
                }

                $payment = Payment::create([
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'employee_id' => $employee->id,
                    'status' => PaymentStatus::PENDING,
                    'method' => $data['method'],
                    'amount' => $amount,
                    'reference_number' => $data['reference_number'] ?? null,
                    'payment_date' => $data['payment_date'],
                    'description' => $data['description'] ?? null,
                ]);

                return $payment->fresh(Payment::DEFAULT_RELATIONS);
            });
        }
    }
