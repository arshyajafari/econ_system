<?php

    namespace App\Services;

    use App\Enums\CustomerTransactionType;
    use App\Exceptions\BusinessRuleException;
    use App\Models\CustomerTransaction;
    use App\Models\Invoice;
    use App\Models\OrderReturn;
    use App\Models\Payment;
    use Illuminate\Database\Eloquent\Model;

    class CustomerTransactionService {
        public function debit(int $customerId, float|int|string $amount, ?Model $source = null,
            ?string $description = null, $transactionAt = null, ?array $meta = null): CustomerTransaction {
            return $this->create(customerId: $customerId, type: CustomerTransactionType::DEBIT, amount: $amount,
                source: $source, description: $description, transactionAt: $transactionAt, meta: $meta,);
        }

        public function credit(int $customerId, float|int|string $amount, ?Model $source = null,
            ?string $description = null, $transactionAt = null, ?array $meta = null): CustomerTransaction {
            return $this->create(customerId: $customerId, type: CustomerTransactionType::CREDIT, amount: $amount,
                source: $source, description: $description, transactionAt: $transactionAt, meta: $meta,);
        }

        protected function create(int $customerId, CustomerTransactionType $type, float|int|string $amount,
            ?Model $source, ?string $description, $transactionAt, ?array $meta): CustomerTransaction {
            $amount = (float)$amount;

            if ($amount <= 0) {
                throw new BusinessRuleException('مبلغ تراکنش باید بیشتر از صفر باشد.');
            }

            $data = [
                'customer_id' => $customerId,
                'type' => $type,
                'amount' => $amount,
                'transaction_at' => $transactionAt ?? now(),
                'description' => $description,
                'meta' => $meta,
            ];

            $this->attachSource($data, $customerId, $source);

            return CustomerTransaction::create($data);
        }

        protected function attachSource(array &$data, int $customerId, ?Model $source): void {
            if (!$source) {
                return;
            }

            match (true) {
                $source instanceof Invoice => $this->attachInvoiceSource($data, $customerId, $source),

                $source instanceof Payment => $this->attachPaymentSource($data, $customerId, $source),

                $source instanceof OrderReturn => $this->attachOrderReturnSource($data, $customerId, $source),

                default => throw new BusinessRuleException('منبع تراکنش مالی پشتیبانی نمی‌شود.'),
            };
        }

        protected function attachInvoiceSource(array &$data, int $customerId, Invoice $invoice): void {
            if ((int)$invoice->customer_id !== $customerId) {
                throw new BusinessRuleException('فاکتور متعلق به این مشتری نیست.');
            }

            $data['invoice_id'] = $invoice->id;
        }

        protected function attachPaymentSource(array &$data, int $customerId, Payment $payment): void {
            if ((int)$payment->customer_id !== $customerId) {
                throw new BusinessRuleException('پرداخت متعلق به این مشتری نیست.');
            }

            $data['payment_id'] = $payment->id;
        }

        protected function attachOrderReturnSource(array &$data, int $customerId, OrderReturn $orderReturn): void {
            if ((int)$orderReturn->customer_id !== $customerId) {
                throw new BusinessRuleException('مرجوعی متعلق به این مشتری نیست.');
            }

            $data['order_return_id'] = $orderReturn->id;
        }
    }
