<?php

namespace App\Services;

use App\Enums\CustomerTransactionType;
use App\Enums\InvoiceStatus;
use App\Models\CustomerTransaction;
use App\Models\Invoice;
use Carbon\CarbonImmutable;

class CustomerLedgerService {
    public function build(int $customerId, ?string $from = null, ?string $to = null): array {
        $baseQuery = CustomerTransaction::query()->where('customer_id', $customerId);

        $openingBalanceMinor = $this->calculateOpeningBalanceMinor($baseQuery, $from);

        $query = clone $baseQuery;

        if ($from) {
            $query->where('transaction_at', '>=', CarbonImmutable::parse($from)->startOfDay());
        }

        if ($to) {
            $query->where('transaction_at', '<=', CarbonImmutable::parse($to)->endOfDay());
        }

        $transactions = $query
            ->with(CustomerTransaction::DEFAULT_RELATIONS)
            ->orderBy('transaction_at')
            ->orderBy('id')
            ->get();

        $balanceMinor = $openingBalanceMinor;
        $totalDebitMinor = 0;
        $totalCreditMinor = 0;

        $transactions = $transactions->map(function (CustomerTransaction $transaction) use (
            &$balanceMinor, &$totalDebitMinor, &$totalCreditMinor
        ): CustomerTransaction {
            $amountMinor = $this->toMinorUnits($transaction->amount);

            if ($transaction->type === CustomerTransactionType::DEBIT) {
                $debitMinor = $amountMinor;
                $creditMinor = 0;
                $balanceMinor += $amountMinor;
                $totalDebitMinor += $amountMinor;
            } else {
                $debitMinor = 0;
                $creditMinor = $amountMinor;
                $balanceMinor -= $amountMinor;
                $totalCreditMinor += $amountMinor;
            }

            $transaction->debit = $this->fromMinorUnits($debitMinor);
            $transaction->credit = $this->fromMinorUnits($creditMinor);
            $transaction->balance = $this->fromMinorUnits($balanceMinor);
            $transaction->source = $this->sourceData($transaction);

            return $transaction;
        });

        return [
            'opening_balance' => $this->fromMinorUnits($openingBalanceMinor),
            'total_debit' => $this->fromMinorUnits($totalDebitMinor),
            'total_credit' => $this->fromMinorUnits($totalCreditMinor),
            'closing_balance' => $this->fromMinorUnits($balanceMinor),
            'average_due_date' => $this->calculateAverageDueDate($customerId),
            'transactions' => $transactions,
        ];
    }

    protected function calculateAverageDueDate(int $customerId): ?string {
        $dates = Invoice::query()
            ->where('customer_id', $customerId)
            ->where('status', InvoiceStatus::ISSUED)
            ->whereNotNull('due_date')
            ->pluck('due_date');

        if ($dates->isEmpty()) {
            return null;
        }

        $averageTimestamp = $dates
            ->map(fn ($date) => CarbonImmutable::parse($date)->startOfDay()->timestamp)
            ->average();

        return CarbonImmutable::createFromTimestamp((int) round($averageTimestamp))
            ->toDateString();
    }

    protected function calculateOpeningBalanceMinor($baseQuery, ?string $from): int {
        if (!$from) {
            return 0;
        }

        $fromDate = CarbonImmutable::parse($from)->startOfDay();

        $debit = (clone $baseQuery)
            ->where('type', CustomerTransactionType::DEBIT)
            ->where('transaction_at', '<', $fromDate)
            ->sum('amount');

        $credit = (clone $baseQuery)
            ->where('type', CustomerTransactionType::CREDIT)
            ->where('transaction_at', '<', $fromDate)
            ->sum('amount');

        return $this->toMinorUnits($debit) - $this->toMinorUnits($credit);
    }

    /**
     * Convert a DECIMAL currency amount to integer minor units without float arithmetic.
     */
    protected function toMinorUnits(float|int|string|null $amount): int {
        $value = trim((string) ($amount ?? '0'));

        if (!preg_match('/^([+-]?)(\\d+)(?:\\.(\\d{1,2}))?$/D', $value, $matches)) {
            throw new \\InvalidArgumentException('Invalid currency amount for customer ledger.');
        }

        $whole = (int) $matches[2];
        $fraction = (int) str_pad($matches[3] ?? '', 2, '0');
        $minorUnits = ($whole * 100) + $fraction;

        return ($matches[1] ?? '') === '-' ? -$minorUnits : $minorUnits;
    }

    protected function fromMinorUnits(int $amount): float {
        return round($amount / 100, 2);
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
