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
            $availableQuantity = (int) $inventory->available_quantity;

            // Deletion is only allowed when the entire received quantity is still
            // available. Consumed quantities are historical usage and must never
            // be silently removed or treated as stock that can be returned.
            if ($receivedQuantity !== $availableQuantity || $usedQuantity !== 0) {
                throw new BusinessRuleException(
                    "حذف این موجودی فقط زمانی مجاز است که تعداد دریافتی و موجودی قابل استفاده برابر باشند. دریافتی: {$receivedQuantity}، مصرف‌شده: {$usedQuantity}، قابل استفاده: {$availableQuantity}."
                );
            }

            $transfers = DB::table('scientific_visitor_inventory_transfers')
                ->where('scientific_visitor_inventory_id', $inventory->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            $activeTransferred = (int) $transfers->sum(
                fn ($transfer) => (int) $transfer->quantity - (int) $transfer->returned_quantity
            );
            $allocatedUsed = (int) $transfers->sum('used_quantity');

            if ($activeTransferred !== $receivedQuantity || $allocatedUsed !== 0) {
                throw new BusinessRuleException(
                    'برای این موجودی سابقه بچ‌های تحویلی کامل نیست؛ حذف انجام نشد تا موجودی انبار اصلی اشتباه نشود. ابتدا موجودی تاریخی باید تطبیق داده شود.'
                );
            }

            foreach ($transfers as $transfer) {
                $activeQuantity = (int) $transfer->quantity - (int) $transfer->returned_quantity;
                if ($activeQuantity <= 0) {
                    continue;
                }

                $batch = InventoryBatch::query()
                    ->lockForUpdate()
                    ->findOrFail($transfer->inventory_batch_id);

                $batch->quantity += $activeQuantity;
                $batch->save();

                DB::table('scientific_visitor_inventory_transfers')
                    ->where('id', $transfer->id)
                    ->update([
                        'returned_quantity' => (int) $transfer->returned_quantity + $activeQuantity,
                        'updated_at' => now(),
                    ]);

                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'type' => InventoryMovementType::IN,
                    'quantity' => $activeQuantity,
                    'reason' => 'scientific_visitor_transfer_return',
                    'description' => "برگشت موجودی ویزیتور علمی {$inventory->employee?->fullName}",
                    'moved_at' => now(),
                ]);
            }

            $inventory->delete();
        });
    }
}
