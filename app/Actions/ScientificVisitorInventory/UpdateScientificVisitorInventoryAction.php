<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Enums\InventoryMovementType;
use App\Exceptions\BusinessRuleException;
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
                throw new BusinessRuleException(
                    'تغییر تعداد از طریق ویرایش موجودی تجمیعی مجاز نیست؛ تحویل جدید را از مسیر تحویل به ویزیتور ثبت کنید. برگشت یا اصلاح تعداد پس از ثبت ردیابی دقیق بچ‌ها امکان‌پذیر خواهد بود.'
                );
            }

            if (array_key_exists('description', $data)) {
                $inventory->description = $data['description'];
            }

            $inventory->save();

            return $inventory->fresh(ScientificVisitorInventory::DEFAULT_RELATIONS);
        });
    }
}
