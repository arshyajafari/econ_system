<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Enums\InventoryMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\ScientificVisitorInventory;
use Illuminate\Support\Facades\DB;

class UpdateScientificVisitorInventoryTransferAction
{
    public function execute(ScientificVisitorInventory $inventory, int $transferId, array $data): array
    {
        return DB::transaction(function () use ($inventory, $transferId, $data) {
            $ledger = ScientificVisitorInventory::query()->lockForUpdate()->findOrFail($inventory->id);
            $transfer = DB::table('scientific_visitor_inventory_transfers')
                ->where('scientific_visitor_inventory_id', $ledger->id)
                ->where('id', $transferId)->lockForUpdate()->first();

            if (!$transfer) {
                throw new BusinessRuleException('ردیف تحویل پیدا نشد.');
            }

            $oldActive = (int) $transfer->quantity - (int) $transfer->returned_quantity;
            $used = (int) $transfer->used_quantity;
            $newQuantity = (int) $data['quantity'];
            if ($newQuantity < $used) {
                throw new BusinessRuleException("تعداد تحویل نمی‌تواند کمتر از مصرف این ردیف ({$used}) باشد.");
            }

            $delta = $newQuantity - $oldActive;
            if ($delta > 0) {
                throw new BusinessRuleException('افزایش مقدار این ردیف مجاز نیست؛ برای ثبت مقدار بیشتر، یک تحویل جدید ثبت کنید تا سابقه بچ و تاریخ دقیق حفظ شود.');
            } elseif ($delta < 0) {
                $return = abs($delta);
                if ($return > ($oldActive - $used)) {
                    throw new BusinessRuleException('مقدار کاهش از موجودی استفاده‌نشده این ردیف بیشتر است.');
                }
                $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($transfer->inventory_batch_id);
                $batch->quantity += $return;
                $batch->save();
                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id, 'type' => InventoryMovementType::IN,
                    'quantity' => $return, 'reason' => 'scientific_visitor_transfer_return',
                    'description' => $data['description'] ?? 'برگشت اصلاح مقدار تحویل ویزیتور علمی', 'moved_at' => now(),
                ]);
            }

            $updates = [
                'quantity' => (int) $transfer->quantity + max(0, $delta),
                'returned_quantity' => (int) $transfer->returned_quantity + max(0, -$delta),
                'updated_at' => now(),
            ];
            if (!empty($data['delivered_at'])) {
                $updates['delivered_at'] = \Carbon\Carbon::parse($data['delivered_at'])->toDateString();
            }
            DB::table('scientific_visitor_inventory_transfers')->where('id', $transferId)->update($updates);

            $ledger->received_quantity += $delta;
            $ledger->save();

            return (array) DB::table('scientific_visitor_inventory_transfers')->where('id', $transferId)->first();
        });
    }
}
