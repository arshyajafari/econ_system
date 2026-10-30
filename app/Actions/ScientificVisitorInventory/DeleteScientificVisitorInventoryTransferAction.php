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
            if ((int) $transfer->used_quantity > 0) {
                throw new BusinessRuleException('این تحویل دارای نمونه مصرف‌شده است و برای حفظ سوابق قابل حذف نیست؛ مقدار را تا حد مصرف‌شده کاهش دهید.');
            }
            $active = (int) $transfer->quantity - (int) $transfer->returned_quantity;
            if ($active > 0) {
                $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($transfer->inventory_batch_id);
                $batch->quantity += $active;
                $batch->save();
                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id, 'type' => InventoryMovementType::IN,
                    'quantity' => $active, 'reason' => 'scientific_visitor_transfer_return',
                    'description' => 'حذف ردیف تحویل و برگشت به انبار اصلی', 'moved_at' => now(),
                ]);
            }
            DB::table('scientific_visitor_inventory_transfers')->where('id', $transferId)->update([
                'returned_quantity' => (int) $transfer->returned_quantity + $active,
                'updated_at' => now(),
            ]);
            $ledger->received_quantity = max(0, (int) $ledger->received_quantity - $active);
            $ledger->save();
        });
    }
}
