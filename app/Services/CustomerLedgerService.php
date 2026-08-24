<?php

    namespace App\Services;

    use App\Enums\CustomerTransactionType;
    use App\Models\CustomerTransaction;
    use Illuminate\Support\Collection;

    class CustomerLedgerService {
        public function build(int $customerId, ?string $from = null, ?string $to = null): array {
            $baseQuery = CustomerTransaction::query()->where('customer_id', $customerId);

            $openingTransactions = clone $baseQuery;

            if ($from) {
                $openingTransactions->whereDate('transaction_at', '<', $from);
            }

            $openingBalance = $this->calculateBalance($openingTransactions->get());

            $query = clone $baseQuery;

            if ($from) {
                $query->whereDate('transaction_at', '>=', $from);
            }

            if ($to) {
                $query->whereDate('transaction_at', '<=', $to);
            }

            $transactions = $query->with(CustomerTransaction::DEFAULT_RELATIONS)->orderBy('transaction_at')
                ->orderBy('id')->get();

            $balance = $openingBalance;

            $totalDebit = 0;
            $totalCredit = 0;

            $transactions = $transactions->map(function (CustomerTransaction $transaction) use (
                &$balance, &$totalDebit, &$totalCredit
            ) {
                $amount = (float)$transaction->amount;

                $debit = 0;
                $credit = 0;

                if ($transaction->type === CustomerTransactionType::DEBIT) {
                    $debit = $amount;
                    $balance += $amount;
                    $totalDebit += $amount;
                } else {
                    $credit = $amount;
                    $balance -= $amount;
                    $totalCredit += $amount;
                }

                $transaction->debit = $debit;
                $transaction->credit = $credit;
                $transaction->balance = $balance;

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

        protected function calculateBalance(Collection $transactions): float {
            $balance = 0;

            foreach ($transactions as $transaction) {
                $amount = (float)$transaction->amount;

                if ($transaction->type === CustomerTransactionType::DEBIT) {
                    $balance += $amount;
                } else {
                    $balance -= $amount;
                }
            }

            return $balance;
        }
    }
