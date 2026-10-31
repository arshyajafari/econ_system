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

            if ($newQuantity < $usedQuantity) {
                throw new BusinessRuleException(
                    "تعداد دریافتی نمی‌تواند کمتر از تعداد مصرف‌شده باشد ({$usedQuantity} عدد)."
                );
            }

            $transfers = DB::table('scientific_visitor_inventory_transfers')
                ->where('scientific_visitor_inventory_id', $inventory->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $activeTransferred = 0;
            $allocatedUsed = 0;
            foreach ($transfers as $transfer) {
                $transferQuantity = (int) $transfer->quantity;
                $returnedQuantity = (int) $transfer->returned_quantity;
                $transferUsed = (int) $transfer->used_quantity;
                $activeQuantity = $transferQuantity - $returnedQuantity;

                if ($transferQuantity < 0 || $returnedQuantity < 0
                    || $returnedQuantity > $transferQuantity || $transferUsed < 0
                    || $transferUsed > $activeQuantity) {
                    throw new BusinessRuleException(
                        'سوابق یکی از بچ‌های تحویلی نامعتبر است؛ ویرایش انجام نشد تا موجودی انبار اصلی اشتباه نشود.'
                    );
                }

                $activeTransferred += $activeQuantity;
                $allocatedUsed += $transferUsed;
            }
            $isFullyTracked = $activeTransferred === $oldQuantity && $allocatedUsed === $usedQuantity;

            if ($newQuantity !== $oldQuantity && !$isFullyTracked) {
                throw new BusinessRuleException(
                    'برای این موجودی سابقه بچ‌های تحویلی کامل نیست؛ تغییر تعداد به‌صورت خودکار انجام نشد تا موجودی انبار اصلی اشتباه نشود. ابتدا موجودی تاریخی باید تطبیق داده شود.'
                );
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

                DB::table('scientific_visitor_inventory_transfers')->insert([
                    'scientific_visitor_inventory_id' => $inventory->id,
                    'inventory_batch_id' => $batch->id,
                    'quantity' => $delta,
                    'used_quantity' => 0,
                    'returned_quantity' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'type' => InventoryMovementType::OUT,
                    'quantity' => $delta,
                    'reason' => 'scientific_visitor_transfer',
                    'description' => $data['description'] ?? "افزایش موجودی ویزیتور علمی {$inventory->employee?->fullName}",
                    'moved_at' => now(),
                ]);
            } elseif ($newQuantity < $oldQuantity) {
                $toReturn = $oldQuantity - $newQuantity;
                $reverseTransfers = $transfers->sortByDesc('id');

                foreach ($reverseTransfers as $transfer) {
                    $activeQuantity = (int) $transfer->quantity - (int) $transfer->returned_quantity;
                    $returnable = $activeQuantity - (int) $transfer->used_quantity;
                    if ($returnable <= 0) {
                        continue;
                    }

                    $quantity = min($toReturn, $returnable);
                    $batch = InventoryBatch::query()
                        ->lockForUpdate()
                        ->findOrFail($transfer->inventory_batch_id);

                    $batch->quantity += $quantity;
                    $batch->save();

                    DB::table('scientific_visitor_inventory_transfers')
                        ->where('id', $transfer->id)
                        ->update([
                            'returned_quantity' => (int) $transfer->returned_quantity + $quantity,
                            'updated_at' => now(),
                        ]);

                    InventoryMovement::create([
                        'inventory_batch_id' => $batch->id,
                        'type' => InventoryMovementType::IN,
                        'quantity' => $quantity,
                        'reason' => 'scientific_visitor_transfer_return',
                        'description' => $data['description'] ?? "برگشت موجودی ویزیتور علمی {$inventory->employee?->fullName}",
                        'moved_at' => now(),
                    ]);

                    $toReturn -= $quantity;
                    if ($toReturn === 0) {
                        break;
                    }
                }

                if ($toReturn !== 0) {
                    throw new BusinessRuleException(
                        'کاهش تعداد از موجودی باقی‌مانده ویزیتور بیشتر است؛ ابتدا نمونه‌های ثبت‌شده را اصلاح کنید.'
                    );
                }
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
