<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Enums\InventoryMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\ScientificVisitorInventory;
use Illuminate\Support\Facades\DB;

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
            $delta = $newQuantity - $oldQuantity;

            if ($newQuantity < $usedQuantity) {
                throw new BusinessRuleException(
                    "تعداد دریافتی نمی‌تواند کمتر از تعداد مصرف‌شده باشد ({$usedQuantity} عدد)."
                );
            }

            if ($delta !== 0) {
                if (empty($data['inventory_batch_id'])) {
                    throw new BusinessRuleException('برای تغییر تعداد، انتخاب بچ محصول الزامی است.');
                }

                $batch = InventoryBatch::query()
                    ->where('public_id', $data['inventory_batch_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $batch->product_id !== (int) $inventory->product_id) {
                    throw new BusinessRuleException('بچ انتخاب‌شده مربوط به این محصول نیست.');
                }

                if ($batch->is_expired) {
                    throw new BusinessRuleException('اصلاح موجودی با بچ منقضی‌شده مجاز نیست.');
                }

                $movementType = $delta > 0
                    ? InventoryMovementType::OUT
                    : InventoryMovementType::IN;

                $movementQuantity = abs($delta);

                if ($delta > 0) {
                    if ($movementQuantity > (int) $batch->available_quantity) {
                        throw new BusinessRuleException('موجودی قابل تحویل این بچ برای افزایش نمونه کافی نیست.');
                    }

                    $batch->quantity -= $movementQuantity;
                } else {
                    $batch->quantity += $movementQuantity;
                }

                $batch->save();

                $inventory->received_quantity = $newQuantity;

                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'type' => $movementType,
                    'quantity' => $movementQuantity,
                    'reason' => 'scientific_visitor_inventory_adjustment',
                    'description' => $delta > 0
                        ? "افزایش نمونه تحویلی به {$inventory->employee?->fullName} از {$oldQuantity} به {$newQuantity}"
                        : "برگشت نمونه از {$inventory->employee?->fullName}؛ کاهش نمونه تحویلی از {$oldQuantity} به {$newQuantity}",
                    'moved_at' => now(),
                ]);

                if ($delta > 0) {
                    $inventory->last_received_at = now();
                }
            }

            if (array_key_exists('description', $data)) {
                $inventory->description = $data['description'];
            }

            $inventory->save();

            return $inventory->fresh(ScientificVisitorInventory::DEFAULT_RELATIONS);
        });
    }
}
