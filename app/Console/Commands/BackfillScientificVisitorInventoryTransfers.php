<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BackfillScientificVisitorInventoryTransfers extends Command
{
    protected $signature = 'scientific-inventory:backfill-transfers
        {--batch-map= : Explicit mapping of internal ledger ID to batch ID, e.g. 12:8,13:9}
        {--apply : Persist the generated transfer rows; without this option the command is dry-run only}';

    protected $description = 'Backfill legacy scientific visitor inventory into batch-linked transfers using each ledger created_at; dry-run by default.';

    public function handle(): int
    {
        if (!Schema::hasTable('scientific_visitor_inventories')
            || !Schema::hasTable('scientific_visitor_inventory_transfers')
            || !Schema::hasTable('inventory_batches')) {
            $this->error('Required inventory tables are missing. Run the relevant migrations first.');
            return self::FAILURE;
        }

        $batchMap = $this->parseBatchMap((string) $this->option('batch-map'));
        if ($batchMap === null) {
            $this->error('Invalid --batch-map. Expected comma-separated internal IDs in ledgerId:batchId format, e.g. 12:8,13:9.');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $ledgers = DB::table('scientific_visitor_inventories')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $report = [];
        $ready = [];

        foreach ($ledgers as $ledger) {
            $totals = DB::table('scientific_visitor_inventory_transfers')
                ->where('scientific_visitor_inventory_id', $ledger->id)
                ->selectRaw('COALESCE(SUM(quantity - returned_quantity), 0) as received')
                ->selectRaw('COALESCE(SUM(used_quantity), 0) as used')
                ->first();

            $remainingReceived = (int) $ledger->received_quantity - (int) $totals->received;
            $remainingUsed = (int) $ledger->used_quantity - (int) $totals->used;

            if ($remainingReceived === 0 && $remainingUsed === 0) {
                continue;
            }

            $status = 'needs_batch_mapping';
            $batchId = $batchMap[$ledger->id] ?? null;
            $reason = 'No explicit source batch mapping supplied.';

            if ($remainingReceived < 0 || $remainingUsed < 0 || $remainingUsed > $remainingReceived) {
                $status = 'quantity_mismatch';
                $reason = 'Aggregate ledger quantities do not reconcile with existing transfers.';
            } elseif ($remainingReceived <= 0) {
                $status = 'quantity_mismatch';
                $reason = 'Used quantity remains but no untracked received quantity is available.';
            } elseif ($batchId !== null) {
                $batch = DB::table('inventory_batches')->where('id', $batchId)->first();
                if (!$batch) {
                    $status = 'invalid_batch';
                    $reason = 'Mapped inventory batch does not exist.';
                } elseif ((int) $batch->product_id !== (int) $ledger->product_id) {
                    $status = 'product_mismatch';
                    $reason = 'Mapped batch belongs to a different product.';
                } else {
                    $status = 'ready';
                    $reason = 'Explicit batch mapping and aggregate quantities validated.';
                    $ready[] = [
                        'ledger_id' => (int) $ledger->id,
                        'batch_id' => (int) $batchId,
                        'quantity' => $remainingReceived,
                        'used_quantity' => $remainingUsed,
                        'created_at' => $ledger->created_at,
                        'updated_at' => $ledger->updated_at ?? $ledger->created_at,
                    ];
                }
            }

            $report[] = [
                'ledger_id' => (int) $ledger->id,
                'employee_id' => $ledger->employee_id,
                'product_id' => $ledger->product_id,
                'ledger_created_at' => $ledger->created_at,
                'ledger_received' => (int) $ledger->received_quantity,
                'tracked_received' => (int) $totals->received,
                'remaining_received' => $remainingReceived,
                'ledger_used' => (int) $ledger->used_quantity,
                'tracked_used' => (int) $totals->used,
                'remaining_used' => $remainingUsed,
                'batch_id' => $batchId,
                'status' => $status,
                'reason' => $reason,
            ];
        }

        $this->table(
            ['Ledger ID', 'Employee ID', 'Product ID', 'Created At', 'Remaining Qty', 'Remaining Used', 'Batch ID', 'Status', 'Reason'],
            array_map(fn ($row) => [
                $row['ledger_id'], $row['employee_id'], $row['product_id'], $row['ledger_created_at'],
                $row['remaining_received'], $row['remaining_used'], $row['batch_id'] ?? '-',
                $row['status'], $row['reason'],
            ], $report)
        );

        $this->info('Rows requiring reconciliation: '.count($report));
        $this->info('Rows validated for insertion: '.count($ready));

        if (!$apply) {
            $this->warn('DRY RUN ONLY: no database rows were changed.');
            $this->line('Supply --batch-map=ledgerId:batchId,... after verifying the original source batches.');
            $this->line('Then rerun with --apply to insert only validated rows.');
            return self::SUCCESS;
        }

        if (count($ready) === 0) {
            $this->warn('Nothing was inserted. Add explicit, verified batch mappings for eligible ledger rows.');
            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($ready): void {
                foreach ($ready as $row) {
                    // Recheck inside the transaction to make repeat runs idempotent.
                    $ledger = DB::table('scientific_visitor_inventories')
                        ->where('id', $row['ledger_id'])
                        ->lockForUpdate()
                        ->first();

                    if (!$ledger) {
                        throw new \RuntimeException('Ledger '.$row['ledger_id'].' no longer exists.');
                    }

                    $totals = DB::table('scientific_visitor_inventory_transfers')
                        ->where('scientific_visitor_inventory_id', $row['ledger_id'])
                        ->selectRaw('COALESCE(SUM(quantity - returned_quantity), 0) as received')
                        ->selectRaw('COALESCE(SUM(used_quantity), 0) as used')
                        ->first();

                    $remainingReceived = (int) $ledger->received_quantity - (int) $totals->received;
                    $remainingUsed = (int) $ledger->used_quantity - (int) $totals->used;

                    if ($remainingReceived !== $row['quantity'] || $remainingUsed !== $row['used_quantity']) {
                        throw new \RuntimeException('Ledger '.$row['ledger_id'].' changed after validation; rerun dry-run.');
                    }

                    $batch = DB::table('inventory_batches')->where('id', $row['batch_id'])->lockForUpdate()->first();
                    if (!$batch || (int) $batch->product_id !== (int) $ledger->product_id) {
                        throw new \RuntimeException('Batch mapping for ledger '.$row['ledger_id'].' is no longer valid.');
                    }

                    DB::table('scientific_visitor_inventory_transfers')->insert([
                        'scientific_visitor_inventory_id' => $row['ledger_id'],
                        'inventory_batch_id' => $row['batch_id'],
                        'quantity' => $row['quantity'],
                        'used_quantity' => $row['used_quantity'],
                        'returned_quantity' => 0,
                        'created_at' => $ledger->created_at,
                        'updated_at' => $ledger->updated_at ?? $ledger->created_at,
                    ]);
                }
            });
        } catch (Throwable $e) {
            $this->error('Backfill aborted and rolled back: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->info('Successfully inserted '.count($ready).' transfer rows.');
        $this->warn('This is a tracking backfill only: it does not change inventory batch quantities or create inventory movements.');
        return self::SUCCESS;
    }

    /**
     * @return array<int, int>|null
     */
    private function parseBatchMap(string $input): ?array
    {
        $input = trim($input);
        if ($input === '') {
            return [];
        }

        $map = [];
        foreach (explode(',', $input) as $pair) {
            if (!preg_match('/^\s*(\d+)\s*:\s*(\d+)\s*$/', $pair, $matches)) {
                return null;
            }
            $ledgerId = (int) $matches[1];
            $batchId = (int) $matches[2];
            if (isset($map[$ledgerId])) {
                return null;
            }
            $map[$ledgerId] = $batchId;
        }

        return $map;
    }
}
