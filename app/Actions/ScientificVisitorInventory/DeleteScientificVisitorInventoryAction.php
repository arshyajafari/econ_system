<?php

namespace App\\Actions\\ScientificVisitorInventory;

use App\\Enums\\InventoryMovementType;
use App\\Exceptions\\BusinessRuleException;
use App\\Models\\InventoryBatch;
use App\\Models\\InventoryMovement;
use App\\Models\\ScientificVisitorInventory;
use Illuminate\\Support\\Facades\\DB;

class DeleteScientificVisitorInventoryAction
{
    public function execute(ScientificVisitorInventory $inventory): void
    {
        DB::transaction(function () use ($inventory): void {
            $inventory = ScientificVisitorInventory::query()
                ->with('employee')
                ->lockForUpdate()
                ->findOrFail($inventory->id);

            $received = (int) $inventory->received_quantity;
            $used = (int) $inventory->used_quantity;

            if ($received < 0 || $used < 0 || $used > $received) {
                throw new BusinessRuleException('اطلاعات موجودی ویزیتور نامعتبر است؛ حذف انجام نشد.');
            }

            $transfers = DB::table('scientific_visitor_inventory_transfers')
                ->where('scientific_visitor_inventory_id', $inventory->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            $activeTotal = 0;
            $allocatedUsed = 0;

            foreach ($transfers as $transfer) {
                $quantity = (int) $transfer->quantity;
                $returned = (int) $transfer->returned_quantity;
                $transferUsed = (int) $transfer->used_quantity;
                $active = $quantity - $returned;

                if ($quantity < 0 || $returned < 0 || $returned > $quantity
                    || $transferUsed < 0 || $transferUsed > $active) {
                    throw new BusinessRuleException('سوابق یکی از بچ‌های تحویلی نامعتبر است؛ هیچ تغییری در انبار اعمال نشد.');
                }

                $activeTotal += $active;
                $allocatedUsed += $transferUsed;
            }

            // Never reconcile stock from incomplete or contradictory historical ledgers.
            if ($activeTotal !== $received || $allocatedUsed !== $used) {
                throw new BusinessRuleException(
                    'سوابق بچ‌های تحویلی با موجودی و مصرف ثبت‌شده تطبیق ندارد؛ حذف انجام نشد تا موجودی انبار اصلی اشتباه نشود.'
                );
            }

            foreach ($transfers as $transfer) {
                $active = (int) $transfer->quantity - (int) $transfer->returned_quantity;
                $returnable = $active - (int) $transfer->used_quantity;

                if ($returnable === 0) {
                    continue;
                }

                $batch = InventoryBatch::query()
                    ->withTrashed()
                    ->lockForUpdate()
                    ->find($transfer->inventory_batch_id);

                if (!$batch) {
                    throw new BusinessRuleException('بچ اصلی یکی از تحویل‌ها پیدا نشد؛ حذف انجام نشد.');
                }

                $batch->quantity += $returnable;
                $batch->save();

                DB::table('scientific_visitor_inventory_transfers')
                    ->where('id', $transfer->id)
                    ->update([
                        'returned_quantity' => (int) $transfer->returned_quantity + $returnable,
                        'updated_at' => now(),
                    ]);

                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'type' => InventoryMovementType::IN,
                    'quantity' => $returnable,
                    'reason' => 'scientific_visitor_transfer_return',
                    'description' => "برگشت موجودی استفاده‌نشده ویزیتور علمی {$inventory->employee?->fullName}",
                    'moved_at' => now(),
                ]);
            }

            // Consumed units remain on the aggregate so sample history and future
            // inventory reconciliation continue to reference a live ledger.
            $inventory->received_quantity = $used;
            $inventory->save();

            if ($used === 0) {
                $inventory->delete();
            }
        });
    }
}
