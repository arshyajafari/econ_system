<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::transaction(function () {
            /*
             * Repair historical completed returns that were recorded as
             * customer credit but were never allocated to their originating
             * invoice.
             *
             * A return only reduces its originating invoice if that invoice
             * still had a receivable balance when the return was completed.
             * A return completed after full settlement remains reusable
             * customer credit and must not reopen the invoice.
             */
            DB::table('customer_transactions as ct')
                ->join('order_returns as orr', 'orr.id', '=', 'ct.order_return_id')
                ->join('orders as o', 'o.id', '=', 'orr.order_id')
                ->join('invoices as i', 'i.order_id', '=', 'o.id')
                ->where('ct.type', 'credit')
                ->where('orr.status', 'completed')
                ->orderBy('ct.transaction_at')
                ->orderBy('ct.id')
                ->select([
                    'ct.id as source_transaction_id',
                    'ct.customer_id',
                    'ct.amount as source_amount',
                    'ct.transaction_at',
                    'i.id as invoice_id',
                    'i.total_amount',
                    'i.code as invoice_code',
                    'orr.code as return_code',
                ])
                ->chunkById(100, function ($transactions) {
                    foreach ($transactions as $transaction) {
                        $alreadyAllocatedFromSource = (float) DB::table('customer_credit_allocations')
                            ->where('source_transaction_id', $transaction->source_transaction_id)
                            ->sum('amount');

                        $sourceAvailable = max(
                            0,
                            round((float) $transaction->source_amount - $alreadyAllocatedFromSource, 2),
                        );

                        if ($sourceAvailable <= 0) {
                            continue;
                        }

                        // Only payments confirmed no later than the return
                        // can make the invoice settled before this return.
                        $confirmedPaymentsBeforeReturn = (float) DB::table('payments')
                            ->where('invoice_id', $transaction->invoice_id)
                            ->where('status', 'confirmed')
                            ->where('payment_date', '<=', $transaction->transaction_at)
                            ->sum(DB::raw('amount + settlement_discount_amount'));

                        // Include allocations created by earlier return sources
                        // for the same invoice.
                        $previousReturnAllocations = (float) DB::table('customer_credit_allocations as cca')
                            ->join(
                                'customer_transactions as previous_ct',
                                'previous_ct.id',
                                '=',
                                'cca.source_transaction_id',
                            )
                            ->where('cca.invoice_id', $transaction->invoice_id)
                            ->where('previous_ct.type', 'credit')
                            ->whereNotNull('previous_ct.order_return_id')
                            ->where(function ($query) use ($transaction) {
                                $query->where('previous_ct.transaction_at', '<', $transaction->transaction_at)
                                    ->orWhere(function ($query) use ($transaction) {
                                        $query->where('previous_ct.transaction_at', '=', $transaction->transaction_at)
                                            ->where('previous_ct.id', '<', $transaction->source_transaction_id);
                                    });
                            })
                            ->sum('cca.amount');

                        $invoiceRemainingAtReturn = max(
                            0,
                            round(
                                (float) $transaction->total_amount
                                - $confirmedPaymentsBeforeReturn
                                - $previousReturnAllocations,
                                2,
                            ),
                        );

                        if ($invoiceRemainingAtReturn <= 0) {
                            continue;
                        }

                        $allocationAmount = min($sourceAvailable, $invoiceRemainingAtReturn);

                        if ($allocationAmount <= 0) {
                            continue;
                        }

                        DB::table('customer_credit_allocations')->insert([
                            'customer_id' => $transaction->customer_id,
                            'source_transaction_id' => $transaction->source_transaction_id,
                            'invoice_id' => $transaction->invoice_id,
                            'amount' => $allocationAmount,
                            'allocated_at' => $transaction->transaction_at,
                            'description' => "کسر مبلغ مرجوعی {$transaction->return_code} از فاکتور {$transaction->invoice_code}",
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
        });
    }

    public function down(): void {
        // Financial repair is intentionally not reversible.
    }
};
