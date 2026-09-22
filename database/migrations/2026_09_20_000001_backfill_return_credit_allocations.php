<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::transaction(function () {
            DB::table('customer_transactions as ct')
                ->join('order_returns as orr', 'orr.id', '=', 'ct.order_return_id')
                ->join('orders as o', 'o.id', '=', 'orr.order_id')
                ->join('invoices as i', 'i.order_id', '=', 'o.id')
                ->where('ct.type', 'credit')
                ->where('orr.status', 'completed')
                ->orderBy('ct.transaction_at')
                ->orderBy('ct.id')
                ->select([
                    'ct.id as chunk_id',
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
                        $alreadyAllocated = (float) DB::table('customer_credit_allocations')
                            ->where('source_transaction_id', $transaction->source_transaction_id)
                            ->sum('amount');

                        $sourceAvailable = max(
                            0,
                            round((float) $transaction->source_amount - $alreadyAllocated, 2),
                        );

                        if ($sourceAvailable <= 0) {
                            continue;
                        }

                        /*
                         * Only backfill against invoices that are still actually
                         * receivable today. A settled historical invoice must not
                         * be reopened; its return remains reusable customer credit.
                         */
                        $confirmedPayments = (float) DB::table('payments')
                            ->where('invoice_id', $transaction->invoice_id)
                            ->where('status', 'confirmed')
                            ->sum(DB::raw('amount + settlement_discount_amount'));

                        $appliedCredits = (float) DB::table('customer_credit_allocations')
                            ->where('invoice_id', $transaction->invoice_id)
                            ->sum('amount');

                        $invoiceRemaining = max(
                            0,
                            round(
                                (float) $transaction->total_amount
                                - $confirmedPayments
                                - $appliedCredits,
                                2,
                            ),
                        );

                        if ($invoiceRemaining <= 0) {
                            continue;
                        }

                        $allocationAmount = min($sourceAvailable, $invoiceRemaining);

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
        /*
         * Deliberately do not delete allocations here: this migration repairs
         * historical financial state, and rolling it back automatically could
         * reopen invoices or recreate customer balances.
         */
    }
};
