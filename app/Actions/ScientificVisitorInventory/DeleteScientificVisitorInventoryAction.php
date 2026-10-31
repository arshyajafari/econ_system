<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Enums\InventoryMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\ScientificVisitorInventory;
use Illuminate\Support\Facades\DB;

class DeleteScientificVisitorInventoryAction
{
    public function execute(ScientificVisitorInventory $inventory): void
    {
        DB::transaction(function () use ($inventory) {
            $inventory = ScientificVisitorInventory::query()
                ->with('employee')
                ->lockForUpdate()
                ->findOrFail($inventory->id);

            $receivedQuantity = (int) $inventory->received_quantity;
            $usedQuantity = (int) $inventory->used_quantity;

            if ($receivedQuantity < 0 || $usedQuantity < 0 || $usedQuantity > $receivedQuantity) {
                throw new BusinessRuleException(
                    'مقادیر موجودی ویزیتور معتبر نیست؛ حذف انجام نشد تا سوابق انبار آسیب نبیند.'
                );
            }

            $transfers = DB::table('scientific_visitor_inventory_transfers')
                ->where('scientific_visitor_inventory_id', $inventory->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            $activeTransferred = 0;
            $allocatedUsed = 0;

            foreach ($transfers as $transfer) {
                $quantity = (int) $transfer->quantity;
                $transferUsed = (int) $transfer->used_quantity;
                $returned = (int) $transfer->returned_quantity;
                $active = $quantity - $returned;

                if ($quantity < 0 || $transferUsed < 0 || $returned < 0
                    || $returned > $quantity || $transferUsed > $active) {
                    throw new BusinessRuleException(
                        'سوابق یکی از بچ‌های تحویلی ناسازگار است؛ حذف انجام نشد تا موجودی انبار اصلی اشتباه نشود.'
                    );
                }

                $activeTransferred += $active;
                $allocatedUsed += $transferUsed;
            }

            // Never return stock unless the source-batch ledger exactly matches
            // the aggregate inventory. This protects against double returns and
            // historical records that predate transfer tracking.
            if ($activeTransferred !== $receivedQuantity || $allocatedUsed !== $usedQuantity) {
                throw new BusinessRuleException(
                    'برای این موجودی سابقه بچ‌های تحویلی کامل یا سازگار نیست؛ حذف انجام نشد تا موجودی انبار اصلی اشتباه نشود. ابتدا سوابق تاریخی باید تطبیق داده شود.'
                );
            }

            foreach ($transfers as $transfer) {
                $activeQuantity = (int) $transfer->quantity - (int) $transfer->returned_quantity;
                $returnableQuantity = $activeQuantity - (int) $transfer->used_quantity;

                if ($returnableQuantity <= 0) {
                    continue;
                }

                $batch = InventoryBatch::query()
                    ->lockForUpdate()
                    ->findOrFail($transfer->inventory_batch_id);

                $batch->quantity += $returnableQuantity;
                $batch->save();

                DB::table('scientific_visitor_inventory_transfers')
                    ->where('id', $transfer->id)
                    ->update([
                        'returned_quantity' => (int) $transfer->returned_quantity + $returnableQuantity,
                        'updated_at' => now(),
                    ]);

                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'type' => InventoryMovementType::IN,
                    'quantity' => $returnableQuantity,
                    'reason' => 'scientific_visitor_transfer_return',
                    'description' => "برگشت موجودی استفاده‌نشده ویزیتور علمی {$inventory->employee?->fullName}",
                    'moved_at' => now(),
                ]);
            }

            if ($usedQuantity === 0) {
                // No consumption depends on this aggregate, so it can be soft-deleted.
                $inventory->received_quantity = 0;
                $inventory->save();
                $inventory->delete();

                return;
            }

            // Preserve the aggregate row when samples have consumed stock. Its
            // received quantity now equals historical consumption, so available
            // quantity is zero while existing sample references remain valid.
            $inventory->received_quantity = $usedQuantity;
            $inventory->save();
        });
    }
}
