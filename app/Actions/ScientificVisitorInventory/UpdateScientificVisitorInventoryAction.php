<?php

namespace App\\Actions\\ScientificVisitorInventory;

use App\\Enums\\InventoryMovementType;
use App\\Exceptions\\BusinessRuleException;
use App\\Models\\InventoryBatch;
use App\\Models\\InventoryMovement;
use App\\Models\\ScientificVisitorInventory;
use Illuminate\\Support\\Facades\\DB;

class UpdateScientificVisitorInventoryAction
{
    public function execute(ScientificVisitorInventory $inventory, array $data): ScientificVisitorInventory
    {
        return DB::transaction(function () use ($inventory, $data) {
            $inventory = ScientificVisitorInventory::query()
                ->with('employee')
                ->lockForUpdate()
                ->findOrFail($inventory->id);

            $newQuantity = (int) $data['quantity'];
            $oldQuantity = (int) $inventory->received_quantity;
            $usedQuantity = (int) $inventory->used_quantity;
            $availableQuantity = max(0, $oldQuantity - $usedQuantity);

            if ($newQuantity < $usedQuantity) {
                throw new BusinessRuleException(
                    "تعداد دریافتی نمی‌تواند کمتر از تعداد مصرف‌شده باشد ({$usedQuantity} عدد)."
                );
            }

            if ($newQuantity < 0 || $newQuantity > $oldQuantity + 1000000) {
                throw new BusinessRuleException('تعداد واردشده معتبر نیست.');
            }

            // Decreases are limited to the currently unused quantity. This
            // aggregate is the source of truth for visitor inventory; editing
            // or deleting it must not depend on the transfer-history table.
            if ($newQuantity < $oldQuantity && ($oldQuantity - $newQuantity) > $availableQuantity) {
                throw new BusinessRuleException('کاهش تعداد فقط از موجودی استفاده‌نشده امکان‌پذیر است.');
            }

            if ($newQuantity > $oldQuantity) {
                if (empty($data['inventory_batch_id'])) {
                    throw new BusinessRuleException('برای افزایش موجودی، بچ انبار اصلی را انتخاب کنید.');
                }

                $batch = InventoryBatch::query()
                    ->where('public_id', $data['inventory_batch_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $batch->product_id !== (int) $inventory->product_id) {
                    throw new BusinessRuleException('بچ انتخاب‌شده مربوط به محصول این ویزیتور نیست.');
                }

                if ($batch->is_expired) {
                    throw new BusinessRuleException('تحویل محصول منقضی‌شده به ویزیتور علمی مجاز نیست.');
                }

                $delta = $newQuantity - $oldQuantity;
                if ((int) $batch->available_quantity < $delta) {
                    throw new BusinessRuleException('موجودی قابل تحویل بچ انتخاب‌شده کافی نیست.');
                }

                $batch->quantity -= $delta;
                $batch->save();

                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'type' => InventoryMovementType::OUT,
                    'quantity' => $delta,
                    'reason' => 'scientific_visitor_transfer',
                    'description' => $data['description'] ?? "افزایش موجودی ویزیتور علمی {$inventory->employee?->fullName}",
                    'moved_at' => now(),
                ]);
            }

            $inventory->received_quantity = $newQuantity;
            if (array_key_exists('description', $data)) {
                $inventory->description = $data['description'];
            }
            $inventory->save();

            return $inventory->fresh(ScientificVisitorInventory::DEFAULT_RELATIONS);
        });
    }
}
