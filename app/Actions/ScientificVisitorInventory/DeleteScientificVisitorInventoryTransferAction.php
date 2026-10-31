<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Enums\InventoryMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\ScientificVisitorInventory;
use Illuminate\Support\Facades\DB;

class DeleteScientificVisitorInventoryTransferAction
{
    public function execute(ScientificVisitorInventory $inventory, int $transferId): void
    {
        DB::transaction(function () use ($inventory, $transferId) {
            $ledger = ScientificVisitorInventory::query()->with('employee')->lockForUpdate()->findOrFail($inventory->id);
            $transfer = DB::table('scientific_visitor_inventory_transfers')
                ->where('scientific_visitor_inventory_id', $ledger->id)
                ->where('id', $transferId)->lockForUpdate()->first();
            if (!$transfer) throw new BusinessRuleException('ردیف تحویل پیدا نشد.');
            $active = (int) $transfer->quantity - (int) $transfer->returned_quantity;
            $used = (int) $transfer->used_quantity;
            if ($used < 0 || $active < $used) {
                throw new BusinessRuleException('سوابق مصرف و برگشت این تحویل ناسازگار است؛ حذف متوقف شد تا موجودی انبار اشتباه نشود.');
            }
            $returnable = $active - $used;
            if ($returnable > 0) {
                $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($transfer->inventory_batch_id);
                $batch->quantity += $returnable;
                $batch->save();
                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id, 'type' => InventoryMovementType::IN,
                    'quantity' => $returnable, 'reason' => 'scientific_visitor_transfer_return',
                    'description' => 'حذف ردیف تحویل و برگشت به انبار اصلی', 'moved_at' => now(),
                ]);
            }
            $ledger->received_quantity = max(0, (int) $ledger->received_quantity - $returnable);
            $ledger->save();

            if ($used > 0) {
                // Keep the transfer row as audit history; only its consumed quantity remains active.
                DB::table('scientific_visitor_inventory_transfers')->where('id', $transferId)->update([
                    'returned_quantity' => (int) $transfer->returned_quantity + $returnable,
                    'updated_at' => now(),
                ]);
            } else {
                // No sample/visit consumption references this transfer: remove the empty transfer record.
                DB::table('scientific_visitor_inventory_transfers')->where('id', $transferId)->delete();
            }

            if ((int) $ledger->received_quantity === 0 && (int) $ledger->used_quantity === 0) {
                $hasOtherTransfers = DB::table('scientific_visitor_inventory_transfers')
                    ->where('scientific_visitor_inventory_id', $ledger->id)->exists();
                if (!$hasOtherTransfers) {
                    $ledger->delete();
                }
            }
        });
    }
}
