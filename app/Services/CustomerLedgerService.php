<?php

    namespace App\Services;

    use App\Enums\CustomerTransactionType;
    use App\Models\CustomerTransaction;
    use Carbon\CarbonImmutable;

    class CustomerLedgerService {
        public function build(int $customerId, ?string $from = null, ?string $to = null): array {
            $baseQuery = CustomerTransaction::query()->where('customer_id', $customerId);

            $openingBalance = $this->calculateOpeningBalance($baseQuery, $from);

            $query = clone $baseQuery;

            if ($from) {
                $fromDate = CarbonImmutable::parse($from)->startOfDay();

                $query->where('transaction_at', '>=', $fromDate);
            }

            if ($to) {
                $toDate = CarbonImmutable::parse($to)->endOfDay();

                $query->where('transaction_at', '<=', $toDate);
            }

            $transactions = $query->with(CustomerTransaction::DEFAULT_RELATIONS)->orderBy('transaction_at')
                ->orderBy('id')->get();

            $balance = $openingBalance;
            $totalDebit = 0.0;
            $totalCredit = 0.0;

            $transactions = $transactions->map(function (CustomerTransaction $transaction) use (
                &$balance, &$totalDebit, &$totalCredit
            ): CustomerTransaction {
                $amount = (float)$transaction->amount;

                if ($transaction->type === CustomerTransactionType::DEBIT) {
                    $debit = $amount;
                    $credit = 0.0;

                    $balance += $amount;
                    $totalDebit += $amount;
                } else {
                    $debit = 0.0;
                    $credit = $amount;

                    $balance -= $amount;
                    $totalCredit += $amount;
                }

                $transaction->debit = $debit;
                $transaction->credit = $credit;
                $transaction->balance = $balance;
                $transaction->source = $this->sourceData($transaction);

                return $transaction;
            });

            return [
                'opening_balance' => $openingBalance,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'closing_balance' => $balance,
                'transactions' => $transactions,
            ];
        }

        protected function calculateOpeningBalance($baseQuery, ?string $from): float {
            if (!$from) {
                return 0.0;
            }

            $fromDate = CarbonImmutable::parse($from)->startOfDay();

            $debit = (clone $baseQuery)->where('type', CustomerTransactionType::DEBIT)
                ->where('transaction_at', '<', $fromDate)->sum('amount');

            $credit = (clone $baseQuery)->where('type', CustomerTransactionType::CREDIT)
                ->where('transaction_at', '<', $fromDate)->sum('amount');

            return (float)$debit - (float)$credit;
        }

        protected function sourceData(CustomerTransaction $transaction): ?array {
            if ($transaction->relationLoaded('invoice') && $transaction->invoice) {
                return [
                    'type' => 'invoice',
                    'id' => $transaction->invoice->public_id,
                    'code' => $transaction->invoice->code,
                ];
            }

            if ($transaction->relationLoaded('payment') && $transaction->payment) {
                return [
                    'type' => 'payment',
                    'id' => $transaction->payment->public_id,
                    'reference_number' => $transaction->payment->reference_number,
                ];
            }

            if ($transaction->relationLoaded('orderReturn') && $transaction->orderReturn) {
                return [
                    'type' => 'order_return',
                    'id' => $transaction->orderReturn->public_id,
                    'code' => $transaction->orderReturn->code,
                ];
            }

            return null;
        }
    }
