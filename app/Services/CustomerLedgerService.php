<?php

namespace App\Services;

use App\Enums\CustomerTransactionType;
use App\Enums\InvoiceStatus;
use App\Models\CustomerTransaction;
use App\Models\Invoice;
use Carbon\CarbonImmutable;

class CustomerLedgerService
{
    /**
     * Build a signed customer ledger.
     *
     * Positive balance = customer owes us.
     * Negative balance = customer has credit with us.
     *
     * All arithmetic is performed in minor currency units to avoid PHP float
     * accumulation errors. Database amounts remain DECIMAL(15,2).
     */
    public function build(int $customerId, ?string $from = null, ?string $to = null): array
    {
        $baseQuery = CustomerTransaction::query()
            ->where('customer_id', $customerId);

        $openingBalanceMinor = $this->calculateOpeningBalanceMinor($baseQuery, $from);

        $query = clone $baseQuery;

        if ($from) {
            $query->where(
                'transaction_at',
                '>=',
                CarbonImmutable::parse($from)->startOfDay(),
            );
        }

        if ($to) {
            $query->where(
                'transaction_at',
                '<=',
                CarbonImmutable::parse($to)->endOfDay(),
            );
        }

        $transactions = $query
            ->with(CustomerTransaction::DEFAULT_RELATIONS)
            ->orderBy('transaction_at')
            ->orderBy('id')
            ->get();

        $balanceMinor = $openingBalanceMinor;
        $totalDebitMinor = 0;
        $totalCreditMinor = 0;

        $transactions = $transactions->map(
            function (CustomerTransaction $transaction) use (
                &$balanceMinor,
                &$totalDebitMinor,
                &$totalCreditMinor,
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

                $transaction->setAttribute('debit', $this->fromMinorUnits($debitMinor));
                $transaction->setAttribute('credit', $this->fromMinorUnits($creditMinor));
                $transaction->setAttribute('balance', $this->fromMinorUnits($balanceMinor));

                return $transaction;
            },
        );

        $averageDueDate = $this->calculateWeightedAverageDueDate($customerId);

        return [
            'opening_balance' => $this->fromMinorUnits($openingBalanceMinor),
            'total_debit' => $this->fromMinorUnits($totalDebitMinor),
            'total_credit' => $this->fromMinorUnits($totalCreditMinor),
            'closing_balance' => $this->fromMinorUnits($balanceMinor),
            'closing_payable' => $this->fromMinorUnits(max(0, $balanceMinor)),
            'closing_customer_credit' => $this->fromMinorUnits(max(0, -$balanceMinor)),
            'balance_status' => $this->balanceStatus($balanceMinor),
            'average_due_date' => $averageDueDate,
            'transactions' => $transactions,
        ];
    }

    protected function calculateOpeningBalanceMinor($baseQuery, ?string $from): int
    {
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
     * Average due date weighted by each currently outstanding invoice amount.
     * Fully settled invoices do not distort the result.
     */
    protected function calculateWeightedAverageDueDate(int $customerId): ?string
    {
        $invoices = Invoice::query()
            ->with([
                'payments',
                'creditAllocations',
                'returnTransactions.orderReturn',
            ])
            ->where('customer_id', $customerId)
            ->where('status', InvoiceStatus::ISSUED)
            ->whereNotNull('due_date')
            ->get();

        $weightedTimestamp = 0.0;
        $totalOutstanding = 0;

        foreach ($invoices as $invoice) {
            $remainingMinor = $this->toMinorUnits($invoice->effectiveRemainingAmount());

            if ($remainingMinor <= 0) {
                continue;
            }

            $dueTimestamp = CarbonImmutable::parse($invoice->due_date)->startOfDay()->timestamp;

            $weightedTimestamp += $dueTimestamp * $remainingMinor;
            $totalOutstanding += $remainingMinor;
        }

        if ($totalOutstanding === 0) {
            return null;
        }

        return CarbonImmutable::createFromTimestamp(
            (int) round($weightedTimestamp / $totalOutstanding),
        )->toDateString();
    }

    protected function balanceStatus(int $balanceMinor): string
    {
        return match (true) {
            $balanceMinor > 0 => 'payable',
            $balanceMinor < 0 => 'customer_credit',
            default => 'settled',
        };
    }

    protected function toMinorUnits(float|int|string|null $amount): int
    {
        return (int) round(((float) ($amount ?? 0)) * 100);
    }

    protected function fromMinorUnits(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
