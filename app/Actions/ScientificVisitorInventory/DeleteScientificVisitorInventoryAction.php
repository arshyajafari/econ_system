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

            $transfers = DB::table('scientific_visitor_inventory_transfers')
                ->where('scientific_visitor_inventory_id', $inventory->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            $activeTransferred = (int) $transfers->sum(
                fn ($transfer) => (int) $transfer->quantity - (int) $transfer->returned_quantity
            );
            $allocatedUsed = (int) $transfers->sum('used_quantity');

            if ($activeTransferred !== (int) $inventory->received_quantity
                || $allocatedUsed !== (int) $inventory->used_quantity) {
                if ((int) $inventory->received_quantity !== 0 || (int) $inventory->used_quantity !== 0) {
                    throw new BusinessRuleException(
                        'برای این موجودی سابقه بچ‌های تحویلی کامل نیست؛ حذف خودکار انجام نشد تا موجودی انبار اصلی اشتباه نشود. ابتدا موجودی تاریخی باید تطبیق داده شود.'
                    );
                }
            }

            foreach ($transfers as $transfer) {
                $activeQuantity = (int) $transfer->quantity - (int) $transfer->returned_quantity;
                $returnable = $activeQuantity - (int) $transfer->used_quantity;
                if ($returnable <= 0) {
                    continue;
                }

                $batch = InventoryBatch::query()
                    ->lockForUpdate()
                    ->findOrFail($transfer->inventory_batch_id);

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
                    'description' => "برگشت موجودی ویزیتور علمی {$inventory->employee?->fullName}",
                    'moved_at' => now(),
                ]);
            }

            // Keep consumed quantities as historical usage; only unconsumed
            // units were returned to the warehouse above.
            $inventory->received_quantity = (int) $inventory->used_quantity;
            $inventory->save();
            $inventory->delete();
        });
    }
}
