<?php

namespace App\Services;

use App\Enums\CustomerTransactionType;
use App\Exceptions\BusinessRuleException;
use App\Models\CustomerCreditAllocation;
use App\Models\CustomerTransaction;
use App\Models\Invoice;
use Illuminate\Support\Collection;

class CustomerCreditService {
    public function availableAmount(int $customerId): float {
        $returnCredits = (float) CustomerTransaction::query()
            ->where('customer_id', $customerId)
            ->where('type', CustomerTransactionType::CREDIT)
            ->whereNotNull('order_return_id')
            ->sum('amount');

        $paymentOverpayments = (float) CustomerTransaction::query()
            ->where('customer_id', $customerId)
            ->where('type', CustomerTransactionType::CREDIT)
            ->whereNotNull('payment_id')
            ->whereHas('payment', fn ($query) => $query->where('status', 'confirmed'))
            ->get()
            ->groupBy('invoice_id')
            ->sum(function (Collection $transactions): float {
                $paymentCredit = (float) $transactions->sum('amount');
                $invoice = $transactions->first()?->payment?->invoice;
                return $invoice ? max(0, round($paymentCredit - (float) $invoice->total_amount, 2)) : 0;
            });

        $allocated = (float) CustomerCreditAllocation::query()
            ->where('customer_id', $customerId)
            ->sum('amount');

        return max(0, round($returnCredits + $paymentOverpayments - $allocated, 2));
    }

    public function allocateToInvoice(int $customerId, Invoice $invoice, float $requestedAmount, ?string $description = null): float {
        $requestedAmount = round($requestedAmount, 2);

        if ($requestedAmount <= 0) {
            return 0.0;
        }

        if ((int) $invoice->customer_id !== $customerId) {
            throw new BusinessRuleException('فاکتور متعلق به این مشتری نیست.');
        }

        $available = $this->availableAmount($customerId);
        $remaining = $invoice->effectiveRemainingAmount();
        $amountToAllocate = min($requestedAmount, $available, $remaining);

        if ($amountToAllocate <= 0) {
            return 0.0;
        }

        $remainingToAllocate = $amountToAllocate;

        $sources = $this->creditSources($customerId);

        foreach ($sources as $source) {
            if ($remainingToAllocate <= 0) {
                break;
            }

            $alreadyAllocated = (float) CustomerCreditAllocation::query()
                ->where('source_transaction_id', $source->id)
                ->sum('amount');

            $sourceAvailable = max(0, round((float) $source->amount - $alreadyAllocated, 2));
            if ($sourceAvailable <= 0) {
                continue;
            }

            $allocationAmount = min($sourceAvailable, $remainingToAllocate);

            CustomerCreditAllocation::create([
                'customer_id' => $customerId,
                'source_transaction_id' => $source->id,
                'invoice_id' => $invoice->id,
                'amount' => $allocationAmount,
                'allocated_at' => now(),
                'description' => $description ?? "استفاده از اعتبار مشتری برای فاکتور {$invoice->code}",
            ]);

            $remainingToAllocate = round($remainingToAllocate - $allocationAmount, 2);
        }

        return round($amountToAllocate - $remainingToAllocate, 2);
    }

    public function invoiceAppliedAmount(Invoice $invoice): float {
        return (float) CustomerCreditAllocation::query()
            ->where('invoice_id', $invoice->id)
            ->sum('amount');
    }

    protected function creditSources(int $customerId): Collection {
        return CustomerTransaction::query()
            ->with(['payment.invoice'])
            ->where('customer_id', $customerId)
            ->where('type', CustomerTransactionType::CREDIT)
            ->where(function ($query) {
                $query->whereNotNull('order_return_id')
                    ->orWhereNotNull('payment_id');
            })
            ->orderBy('transaction_at')
            ->orderBy('id')
            ->get()
            ->filter(function (CustomerTransaction $transaction): bool {
                if ($transaction->order_return_id) {
                    return true;
                }

                if (!$transaction->payment) {
                    return false;
                }

                return $transaction->payment->status?->value === 'confirmed';
            })
            ->values();
    }
}
